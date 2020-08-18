<?php

declare(strict_types=1);

/*
 * Copyright MacFJA
 *
 * Permission is hereby granted, free of charge, to any person obtaining a copy of this software and associated
 * documentation files (the "Software"), to deal in the Software without restriction, including without limitation the
 * rights to use, copy, modify, merge, publish, distribute, sublicense, and/or sell copies of the Software, and to
 * permit persons to whom the Software is furnished to do so, subject to the following conditions:
 *
 * The above copyright notice and this permission notice shall be included in all copies or substantial portions of the
 * Software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR IMPLIED, INCLUDING BUT NOT LIMITED TO THE
 * WARRANTIES OF MERCHANTABILITY, FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE AUTHORS OR
 * COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR
 * OTHERWISE, ARISING FROM, OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE SOFTWARE.
 */

namespace App\Controller\Admin;

use App\Repository\BookRepository;
use App\Worker\BookSearch;
use App\Worker\Search\Index\BookDocument;
use App\Worker\Search\Suggestion\BatchIndexer;
use function array_chunk;
use function array_column;
use EasyCorp\Bundle\EasyAdminBundle\Controller\EasyAdminController;
use Ehann\RediSearch\Exceptions\UnknownIndexNameException;
use Ehann\RediSearch\Index;
use Ehann\RediSearch\Suggestion;
use Ehann\RedisRaw\RedisRawClientInterface;
use Flintstone\Flintstone;
use RuntimeException;
use function sprintf;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

trait SearchEngineTrait
{
    /**
     * @Route ("/search-engine", methods={"GET"}, name="admin_search_engine")
     */
    public function searchEngine(Request $request, Flintstone $flintstone, Suggestion $suggestion, Index $index): Response
    {
        if (!($this instanceof EasyAdminController)) {
            throw new RuntimeException(sprintf(
                'Trait %s can only be used with an instance of %s (currently used with %s)',
                SearchEngineTrait::class,
                EasyAdminController::class,
                self::class
            ));
        }

        $this->initialize($request);

        if (null === $request->query->get('entity')) {
            $engineStats = $index->info();
            $engineStats = array_chunk($engineStats, 2, false);
            $engineStats = array_column($engineStats, 1, 0);

            return $this->render('admin/search-engine.html.twig', [
                'indexInfo' => $engineStats,
                'suggestions' => $suggestion->length(),
                'isSuggestionDirty' => $flintstone->get(\App\Worker\Search\Suggestion::IS_DIRTY_CONFIG_NAME),
            ]);
        }

        return $this->indexAction($request);
    }

    /**
     * @Route("/search-engine/reindex", methods={"GET"}, name="admin_search_engine_reindex")
     *
     * @return RedirectResponse
     */
    public function reindexSearchEngine(Index $index, BookSearch $bookSearch, BookRepository $repository)
    {
        if (!($this instanceof EasyAdminController)) {
            throw new RuntimeException(sprintf(
                'Trait %s can only be used with an instance of %s (currently used with %s)',
                SearchEngineTrait::class,
                EasyAdminController::class,
                self::class
            ));
        }

        try {
            $index->drop();
        } catch (UnknownIndexNameException $exception) {// @phan-suppress-current-line PhanUnusedVariableCaughtException
            // Do nothing
        }
        BookDocument::createIndexDefinition($index);

        foreach ($repository->findAll() as $book) {
            $bookSearch->addBookToIndex($book);
        }

        $this->addFlash('success', 'Search Engine Index rebuild.');

        return $this->redirectToRoute('admin_search_engine');
    }

    /**
     * @Route("/search-engine/reindex-suggestion", methods={"GET"}, name="admin_search_engine_reindex_suggestion")
     *
     * @return RedirectResponse
     */
    public function reindexSuggestion(BookRepository $repository, RedisRawClientInterface $redisClient, BatchIndexer $indexer, string $suggestionIndexName)
    {
        if (!($this instanceof EasyAdminController)) {
            throw new RuntimeException(sprintf(
                'Trait %s can only be used with an instance of %s (currently used with %s)',
                SearchEngineTrait::class,
                EasyAdminController::class,
                self::class
            ));
        }

        $redisClient->rawCommand('DEL', [$suggestionIndexName]);
        $indexer->setPurged(true);

        foreach ($repository->findAll() as $book) {
            $indexer->addToIndex($book);
        }
        $indexer->saveBatch();

        $this->addFlash('success', 'Search Engine Suggestion rebuild.');

        return $this->redirectToRoute('admin_search_engine');
    }
}
