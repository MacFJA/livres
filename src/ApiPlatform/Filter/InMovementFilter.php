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

use ApiPlatform\Core\Bridge\Doctrine\Orm\Filter\AbstractFilter;
use ApiPlatform\Core\Bridge\Doctrine\Orm\Util\QueryNameGeneratorInterface;
use App\Repository\MovementRepository;
use function array_key_exists;
use function array_keys;
use Doctrine\ORM\Query\Expr\Join;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;
use Psr\Log\LoggerInterface;
use function sprintf;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Serializer\NameConverter\NameConverterInterface;

class InMovementFilter extends AbstractFilter
{
    /** @var MovementRepository */
    private $movementRepository;

    /**
     * @param null|array<string,mixed> $properties
     * @phan-suppress PhanUnusedPublicMethodParameter
     * @phan-suppress PhanUndeclaredTypeParameter
     */
    public function __construct(MovementRepository $movementRepository, ManagerRegistry $managerRegistry, ?RequestStack $requestStack = null, ?LoggerInterface $logger = null, ?array $properties = null, ?NameConverterInterface $nameConverter = null)
    {
        parent::__construct($managerRegistry, $requestStack, $logger, $properties, $nameConverter);
        $this->movementRepository = $movementRepository;
    }

    /**
     * {@inheritdoc}
     *
     * @return array<array<mixed>>
     * @suppress PhanUnusedPublicMethodParameter
     */
    public function getDescription(string $resourceClass): array
    {
        $result = [];
        $properties = $this->getProperties() ?? [];
        foreach (array_keys($properties) as $property) {
            $result[$property] = [
                'property' => $property,
                'type' => 'bool',
                'required' => false,
            ];
        }

        return $result;
    }

    /**
     * {@inheritdoc}
     *
     * @param null|mixed $value
     *
     * @return void
     * @suppress PhanUnusedProtectedMethodParameter
     */
    protected function filterProperty(string $property, $value, QueryBuilder $queryBuilder, QueryNameGeneratorInterface $queryNameGenerator, string $resourceClass, ?string $operationName = null)
    {
        if (!array_key_exists($property, $this->getProperties() ?? [])) {
            return;
        }
        $mainAlias = $queryBuilder->getRootAliases()[0];
        if ('true' === $value) {
            $queryBuilder
                ->innerJoin(sprintf('%s.movements', $mainAlias), 'm', Join::WITH, sprintf('m.book= %s', $mainAlias))
                ->andWhere('m.endAt IS NULL');
        } elseif ('false' === $value) {
            $subQuery = $this->movementRepository->createQueryBuilder('m');
            $subQuery->select('IDENTITY(m.book)')
                ->andWhere('m.endAt IS NULL');
            $queryBuilder
                ->andWhere(sprintf('%s NOT IN (%s)', $mainAlias, $subQuery->getDQL()));
        }
    }
}
