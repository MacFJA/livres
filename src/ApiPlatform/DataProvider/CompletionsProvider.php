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
use ApiPlatform\Core\DataProvider\ItemDataProviderInterface;
use ApiPlatform\Core\DataProvider\RestrictedDataProviderInterface;
use App\Model\Completion;
use App\Repository\BookRepository;
use function array_column;
use function array_filter;
use function array_map;
use function array_merge;
use function array_reduce;
use function array_unique;
use function array_values;
use function in_array;
use function is_array;
use function is_string;

class CompletionsProvider implements ContextAwareCollectionDataProviderInterface, ItemDataProviderInterface, RestrictedDataProviderInterface
{
    /** @var BookRepository */
    private $bookRepository;

    /** @var array<string> */
    private $completionsType;

    /**
     * @param string[] $completionsType
     */
    public function __construct(BookRepository $bookRepository, array $completionsType)
    {
        $this->bookRepository = $bookRepository;
        $this->completionsType = $completionsType;
    }

    /**
     * @param array<mixed> $context
     */
    public function supports(string $resourceClass, ?string $operationName = null, array $context = []): bool
    {
        return Completion::class === $resourceClass
            && 'get' === $operationName
            && in_array($context['operation_type'], ['collection', 'item'], true);
    }

    /**
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     * @suppress PhanUnusedPublicMethodParameter
     *
     * @param array<mixed> $context
     *
     * @return array<Completion>
     */
    public function getCollection(string $resourceClass, ?string $operationName = null, array $context = []): array
    {
        return array_map(function (string $completionType): Completion {
            return new Completion($completionType, $this->getAllValues($completionType));
        }, $this->completionsType);
    }

    /**
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     * @SuppressWarnings(PHPMD.ShortVariable)
     * @suppress PhanUnusedPublicMethodParameter
     *
     * @param array<mixed>|int|string $id
     * @param array<mixed>            $context
     */
    public function getItem(string $resourceClass, $id, ?string $operationName = null, array $context = []): ?Completion
    {
        if (!is_string($id) || !in_array($id, $this->completionsType, true)) {
            return null;
        }

        return new Completion($id, $this->getAllValues($id));
    }

    /**
     * @return array<string>
     */
    private function getAllValues(string $field): array
    {
        $values = $this->bookRepository->createQueryBuilder('b')
            ->select('b.'.$field)
            ->distinct()
            ->getQuery()
            ->getArrayResult();

        $values = array_column($values, $field);
        $values = array_reduce($values, function ($carry, $item) {
            if (is_array($item)) {
                return array_merge($carry, $item);
            }
            $carry[] = $item;

            return $carry;
        }, []);

        return array_values(array_filter(array_unique($values)));
    }
}
