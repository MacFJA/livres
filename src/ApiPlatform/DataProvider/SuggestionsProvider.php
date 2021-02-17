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

namespace App\ApiPlatform\DataProvider;

use ApiPlatform\Core\DataProvider\ContextAwareCollectionDataProviderInterface;
use ApiPlatform\Core\DataProvider\RestrictedDataProviderInterface;
use App\Entity\Book;
use App\Model\Output\SuggestionSearchResult;
use App\Model\SuggestionSearch;
use function array_keys;
use function array_map;
use function array_values;
use MacFJA\RediSearch\Integration\ObjectManager;

class SuggestionsProvider implements ContextAwareCollectionDataProviderInterface, RestrictedDataProviderInterface
{
    /** @var ObjectManager */
    private $objectManager;

    public function __construct(ObjectManager $objectManager)
    {
        $this->objectManager = $objectManager;
    }

    /**
     * @param array<mixed> $context
     */
    public function supports(string $resourceClass, ?string $operationName = null, array $context = []): bool
    {
        return SuggestionSearch::class === $resourceClass
            && 'get' === $operationName
            && 'collection' === $context['operation_type'];
    }

    /**
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     * @suppress PhanUnusedPublicMethodParameter
     *
     * @param array<mixed> $context
     *
     * @return array<SuggestionSearchResult>
     */
    public function getCollection(string $resourceClass, ?string $operationName = null, array $context = []): array
    {
        $query = $context['filters']['query'] ?? null;

        if (null === $query) {
            return [];
        }

        $results = $this->objectManager->getSuggestions(Book::class, $query);

        return array_map(function (string $group, array $suggestions) {
            return new SuggestionSearchResult($group, $suggestions);
        }, array_keys($results), array_values($results));
    }
}
