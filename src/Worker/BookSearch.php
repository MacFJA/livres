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

namespace App\Worker;

use App\Entity\Book;
use App\Worker\Search\Index\BookDocument;
use App\Worker\Search\Suggestion\IndividualIndexer;
use function array_column;
use function array_merge;
use function array_reduce;
use function array_walk;
use function compact;
use function count;
use Ehann\RediSearch\Exceptions\RediSearchException;
use Ehann\RediSearch\Index;
use Ehann\RediSearch\Suggestion;
use function get_class;
use function is_string;
use Psr\Log\LoggerInterface;

class BookSearch
{
    private const PAGE_SIZE = 1000;

    /** @var Suggestion */
    private $suggestion;

    /** @var Index */
    private $index;

    /** @var LoggerInterface */
    private $logger;

    /** @var IndividualIndexer */
    private $suggestionIndexer;

    public function __construct(Suggestion $suggestion, Index $index, LoggerInterface $logger, IndividualIndexer $suggestionIndexer)
    {
        $this->suggestion = $suggestion;
        $this->index = $index;
        $this->logger = $logger;
        $this->suggestionIndexer = $suggestionIndexer;
    }

    /**
     * @return array<Search\Suggestion>
     */
    public function getSuggestions(string $query): array
    {
        if (0 === $this->suggestion->length()) {
            $this->logger->warning('There are no suggestions. Did you forget to build suggestions index?');

            return [];
        }
        $suggestions = $this->suggestion->get($query, true, true, -1);
        $suggestions = Search\Suggestion::listFromRawRedis($suggestions, true, false);

        return array_reduce($suggestions, function (array $allSuggestions, Search\Suggestion $oneSuggestion) {
            $allPayloads = $oneSuggestion->getArrayPayload();
            foreach ($allPayloads as $payload) {
                $simplified = new Search\Suggestion(
                    $oneSuggestion->getValue(),
                    $oneSuggestion->getScore(),
                    null
                );
                $simplified->setArrayPayload($payload);

                $allSuggestions[] = $simplified;
            }

            return $allSuggestions;
        }, []);
    }

    /**
     * @return array<array<mixed>|int>
     */
    public function getSearch(string $query, ?string $sort = null, bool $onlyIds = false): array
    {
        $results = [];
        $page = 0;
        do {
            $searchResult = $this->doSearch($query, $sort, $onlyIds, $page);
            $page++;
            $results = array_merge($results, $searchResult);
        } while (self::PAGE_SIZE === count($searchResult));

        return $results;
    }

    /**
     * @return array<array<mixed>>
     * @psalm-suppress InvalidReturnStatement
     * @psalm-suppress InvalidReturnType
     * @phan-suppress PhanPartialTypeMismatchReturn
     */
    public function getPartialSearch(string $query): array
    {
        // @phpstan-ignore-next-line
        return $this->doSearch($query, null, false, 0, 6);
    }

    public function addIndexAndSuggestion(Book $book): void
    {
        $this->addBookToIndex($book);
        $this->suggestionIndexer->addToIndex($book);
    }

    public function addBookToIndex(Book $book): void
    {
        $document = BookDocument::createFromBook($book);

        $this->index->add($document);
    }

    public function removeBookFromIndex(Book $book): void
    {
        $this->index->delete(BookDocument::getBookIndexId($book), true);
    }

    /**
     * @return array<array<mixed>|int>
     */
    private function doSearch(string $query, ?string $sort = null, bool $onlyIds = false, int $page = 0, int $pageSize = self::PAGE_SIZE): array
    {
        $searchQuery = $this->index;
        if (is_string($sort)) {
            $searchQuery->sortBy($sort);
        }
        if (true === $onlyIds) {
            $searchQuery->return(['bookId']);
        }

        try {
            $results = $searchQuery
                ->limit($page * $pageSize, $pageSize)
                ->search($query, true);

            if (true === $onlyIds) {
                $booksId = array_column($results->getDocuments(), 'bookId', 'id');
                array_walk($booksId, static function (&$bookId) {
                    $bookId = (int) $bookId;
                });

                return $booksId;
            }

            return $results->getDocuments();
        } catch (RediSearchException $exception) {
            $this->logger->error(
                get_class($exception).': '.$exception->getMessage(),
                compact('query', 'sort', 'onlyIds', 'page', 'pageSize')
            );

            return [];
        }
    }
}
