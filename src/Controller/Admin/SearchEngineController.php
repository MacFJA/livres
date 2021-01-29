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

use App\Entity\Book;
use App\EventSubscriber\RemoveDocumentSubscriber;
use App\Repository\BookRepository;
use App\Worker\Search\ObjectFactory;
use function assert;
use EasyCorp\Bundle\EasyAdminBundle\Router\AdminUrlGenerator;
use Flintstone\Flintstone;
use function is_string;
use function is_subclass_of;
use MacFJA\RediSearch\Integration\MappedClass;
use MacFJA\RediSearch\Integration\MappedClassProvider;
use MacFJA\RediSearch\Integration\ObjectManager;
use Predis\Client;
use RuntimeException;
use function sprintf;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class SearchEngineController extends AbstractController
{
    /**
     * @Route ("/search-engine", methods={"GET"}, name="admin_search_engine")
     */
    public function searchEngine(MappedClassProvider $provider, ObjectFactory $objectFactory, Flintstone $flintstone): Response
    {
        /** @var class-string<MappedClass>|null $mapped */
        $mapped = $provider->getStaticMappedClass(Book::class);
        if (null === $mapped || !is_subclass_of($mapped, MappedClass::class)) {
            throw new RuntimeException(sprintf(
                'The entity %s is not mapped into RediSearch',
                Book::class
            ));
        }

        $suggestionCount = 0;
        foreach ($mapped::getRSSuggestionGroups() as $group) {
            $suggestionCount += $objectFactory->getSuggestion($group)->length();
        }
        $index = $objectFactory->getIndex($mapped::getRSIndexName());

        $engineStats = $index->getStats();

        return $this->render('admin/search-engine.html.twig', [
            'indexInfo' => $engineStats,
            'suggestions' => $suggestionCount,
            'isSuggestionDirty' => 'yes' === $flintstone->get(RemoveDocumentSubscriber::SUGGESTIONS_DIRTY),
        ]);
    }

    /**
     * @Route("/search-engine/reindex", methods={"GET"}, name="admin_search_engine_reindex")
     *
     * @return RedirectResponse
     */
    public function reindexSearchEngine(MappedClassProvider $provider, ObjectFactory $objectFactory, BookRepository $repository, ObjectManager $objectManager, AdminUrlGenerator $urlGenerator)
    {
        /** @var class-string<MappedClass>|null $mapped */
        $mapped = $provider->getStaticMappedClass(Book::class);
        assert(is_string($mapped));
        $objectFactory->getIndex($mapped::getRSIndexName())
            ->delete(true);
        $objectManager->createIndex(Book::class);

        foreach ($repository->findAll() as $book) {
            $objectManager->addObject($book);
        }

        $this->addFlash('success', 'Search Engine Index rebuild.');

        return $this->redirect(
            $urlGenerator->setRoute('admin_search_engine')->generateUrl()
        );
    }

    /**
     * @Route("/search-engine/reindex-suggestion", methods={"GET"}, name="admin_search_engine_reindex_suggestion")
     *
     * @phpstan-param Client<Client> $redisClient
     * @psalm-param Client $redisClient
     *
     * @return RedirectResponse
     */
    public function reindexSuggestion(Flintstone $flintstone, MappedClassProvider $provider, ObjectManager $objectManager, BookRepository $repository, Client $redisClient, AdminUrlGenerator $urlGenerator)
    {
        /** @var class-string<MappedClass>|null $mapped */
        $mapped = $provider->getStaticMappedClass(Book::class);
        if (null === $mapped || !is_subclass_of($mapped, MappedClass::class)) {
            throw new RuntimeException(sprintf(
                'The entity %s is not mapped into RediSearch',
                Book::class
            ));
        }
        foreach ($mapped::getRSSuggestionGroups() as $group) {
            $redisClient->del($group);
        }
        $flintstone->set(RemoveDocumentSubscriber::SUGGESTIONS_DIRTY, 'no');

        foreach ($repository->findAll() as $book) {
            $objectManager->addObjectInSuggestion($book);
        }

        $this->addFlash('success', 'Search Engine Suggestion rebuild.');

        return $this->redirect(
            $urlGenerator->setRoute('admin_search_engine')->generateUrl()
        );
    }
}
