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

namespace App\ApiPlatform;

use ApiPlatform\Core\Bridge\Doctrine\Orm\Filter\AbstractFilter;
use ApiPlatform\Core\Bridge\Doctrine\Orm\Util\QueryNameGeneratorInterface;
use App\Worker\BookSearch;
use function array_values;
use Doctrine\Common\Persistence\ManagerRegistry;
use Doctrine\DBAL\Connection;
use Doctrine\ORM\QueryBuilder;
use Psr\Log\LoggerInterface;
use function sprintf;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Serializer\NameConverter\NameConverterInterface;
use function trim;

class RedisearchFilter extends AbstractFilter
{
    /** @var BookSearch */
    private $bookSearch;

    /**
     * @param null|array<string,mixed> $properties
     * @phan-suppress PhanUnusedPublicMethodParameter
     * @phan-suppress PhanUndeclaredTypeParameter
     */
    public function __construct(BookSearch $bookSearch, ManagerRegistry $managerRegistry, ?RequestStack $requestStack = null, ?LoggerInterface $logger = null, ?array $properties = null, ?NameConverterInterface $nameConverter = null)
    {
        parent::__construct($managerRegistry, $requestStack, $logger, $properties, $nameConverter);
        $this->bookSearch = $bookSearch;
    }

    /**
     * {@inheritdoc}
     *
     * @return array<array<mixed>>
     * @phan-suppress PhanUnusedPublicMethodParameter
     */
    public function getDescription(string $resourceClass): array
    {
        return ['query' => [
            'property' => 'query',
            'type' => 'string',
            'required' => false,
        ]];
    }

    /**
     * {@inheritdoc}
     *
     * @param null|mixed $value
     *
     * @return void
     * @phan-suppress PhanUnusedProtectedMethodParameter
     */
    protected function filterProperty(string $property, $value, QueryBuilder $queryBuilder, QueryNameGeneratorInterface $queryNameGenerator, string $resourceClass, ?string $operationName = null)
    {
        if (!('query' === $property)) {
            return;
        }

        if (null === $value || empty(trim($value))) {
            return;
        }

        $bookIds = $this->bookSearch->getSearch($value, null, true);
        $valueParameter = $queryNameGenerator->generateParameterName($property);
        $queryBuilder
            ->andWhere(sprintf('%s.%s IN (:%s)', $queryBuilder->getRootAliases()[0], 'bookId', $valueParameter))
            ->setParameter($valueParameter, array_values($bookIds), Connection::PARAM_INT_ARRAY);
    }
}
