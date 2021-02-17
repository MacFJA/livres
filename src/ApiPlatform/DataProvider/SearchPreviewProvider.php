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
use App\Model\SearchPreview;
use MacFJA\RediSearch\Integration\ObjectManager;
use MacFJA\RediSearch\Search\Result;
use Symfony\Component\HttpKernel\Exception\UnprocessableEntityHttpException;

class SearchPreviewProvider implements ContextAwareCollectionDataProviderInterface, RestrictedDataProviderInterface
{
    /** @var ObjectManager */
    private $objectManager;

    /**
     * SearchPreviewProvider constructor.
     */
    public function __construct(ObjectManager $objectManager)
    {
        $this->objectManager = $objectManager;
    }

    /**
     * @param array<mixed> $context
     *
     * @return array<Result>
     *
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     * @suppress PhanUnusedPublicMethodParameter
     */
    public function getCollection(string $resourceClass, ?string $operationName = null, array $context = [])
    {
        $query = $context['filters']['query'] ?? null;

        if (null === $query) {
            return [];
        }

        $result = $this->objectManager->getSearchBuilder(Book::class)
            ->withQuery($query)
            ->withResultOffset(0)
            ->withResultLimit(5)
            ->execute();

        if ($result->getTotalCount() > 5) {
            throw new UnprocessableEntityHttpException('Too many results');
        }

        return $result->getItems();
    }

    /**
     * @param array<mixed> $context
     */
    public function supports(string $resourceClass, ?string $operationName = null, array $context = []): bool
    {
        return SearchPreview::class === $resourceClass
            && 'get' === $operationName
            && 'collection' === $context['operation_type'];
    }
}
