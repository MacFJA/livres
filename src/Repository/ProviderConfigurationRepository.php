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

namespace App\Repository;

use App\Entity\ProviderConfiguration;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use MacFJA\BookRetriever\ProviderConfigurationInterface;
use MacFJA\BookRetriever\ProviderInterface;

class ProviderConfigurationRepository extends ServiceEntityRepository implements ProviderConfigurationInterface
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ProviderConfiguration::class);
    }

    /**
     * @return array<string,string>
     */
    public function getParameters(ProviderInterface $provider): array
    {
        /** @var null|ProviderConfiguration $configuration */
        $configuration = $this->findOneBy(['provider' => $provider->getCode()]);

        return ($configuration instanceof ProviderConfiguration) ? $configuration->getParameters() ?? [] : [];
    }

    public function isActive(ProviderInterface $provider): bool
    {
        /** @var null|ProviderConfiguration $configuration */
        $configuration = $this->findOneBy(['provider' => $provider->getCode()]);
        // not active by default
        return ($configuration instanceof ProviderConfiguration) ? (bool) $configuration->getActive() : false;
    }
}
