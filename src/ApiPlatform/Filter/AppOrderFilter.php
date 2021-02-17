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

namespace App\ApiPlatform\Filter;

use ApiPlatform\Core\Bridge\Doctrine\Common\Filter\OrderFilterInterface;
use ApiPlatform\Core\Bridge\Doctrine\Orm\Filter\AbstractContextAwareFilter;
use ApiPlatform\Core\Bridge\Doctrine\Orm\Util\QueryNameGeneratorInterface;
use function array_key_exists;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;
use Flintstone\Flintstone;
use Psr\Log\LoggerInterface;
use function sprintf;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Serializer\NameConverter\NameConverterInterface;

class AppOrderFilter extends AbstractContextAwareFilter implements OrderFilterInterface
{
    /** @var Flintstone */
    private $flintstone;

    // @phpstan-ignore-next-line
    public function __construct(Flintstone $flintstone, ManagerRegistry $managerRegistry, ?RequestStack $requestStack = null, ?LoggerInterface $logger = null, ?array $properties = null, ?NameConverterInterface $nameConverter = null)
    {
        parent::__construct($managerRegistry, $requestStack, $logger, $properties, $nameConverter);
        $this->flintstone = $flintstone;
    }

    /**
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     * @suppress PhanUnusedPublicMethodParameter
     *
     * @return array<mixed>
     */
    public function getDescription(string $resourceClass): array
    {
        return [];
    }

    /**
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     * @suppress PhanUnusedProtectedMethodParameter
     *
     * @param float|int|mixed|string $value
     *
     * @return void
     */
    protected function filterProperty(string $property, $value, QueryBuilder $queryBuilder, QueryNameGeneratorInterface $queryNameGenerator, string $resourceClass, ?string $operationName = null)
    {
        $alias = $queryBuilder->getRootAliases()[0];

        $sort = $this->flintstone->get('default_sort') ?: 'newest';

        $filter = [
            'newest' => [['bookId', self::DIRECTION_DESC]],
            'oldest' => [['bookId', self::DIRECTION_ASC]],
            'title' => [['title', self::DIRECTION_ASC]],
            'series' => [['series', self::DIRECTION_ASC]],
            'sortTitle' => [['sortTitle', self::DIRECTION_ASC]],
            'series-title' => [['series', self::DIRECTION_ASC], ['title', self::DIRECTION_ASC]],
            'series-sortTitle' => [['series', self::DIRECTION_ASC], ['sortTitle', self::DIRECTION_ASC]],
        ];

        if (!array_key_exists($sort, $filter)) {
            $sort = 'newest';
        }

        foreach ($filter[$sort] as $order) {
            $queryBuilder->addOrderBy(sprintf('%s.%s', $alias, $order[0]), $order[1]);
        }
    }
}
