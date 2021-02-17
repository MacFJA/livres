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

use ApiPlatform\Core\DataProvider\ItemDataProviderInterface;
use ApiPlatform\Core\DataProvider\RestrictedDataProviderInterface;
use ApiPlatform\Core\Identifier\CompositeIdentifierParser;
use App\Model\Output\ProviderSearchResult;
use App\Model\ProviderSearch;
use function array_map;
use function count;
use function is_array;
use MacFJA\BookRetriever\ProviderConfigurationInterface;
use MacFJA\BookRetriever\ProviderConfigurator;
use MacFJA\BookRetriever\ProviderInterface;
use MacFJA\BookRetriever\SearchResultInterface;

class ProviderSearchProvider implements ItemDataProviderInterface, RestrictedDataProviderInterface
{
    /** @var iterable<ProviderInterface> */
    private $providers;

    /** @var ProviderConfigurationInterface */
    private $configuration;

    /**
     * @param ProviderInterface[] $providers
     */
    public function __construct(iterable $providers, ProviderConfigurationInterface $configuration)
    {
        $this->providers = $providers;
        $this->configuration = $configuration;
    }

    /**
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     * @SuppressWarnings(PHPMD.ShortVariable)
     * @suppress PhanUnusedPublicMethodParameter
     *
     * @param array<mixed>|int|string $id
     * @param array<mixed>            $context
     */
    public function getItem(string $resourceClass, $id, ?string $operationName = null, array $context = []): ?ProviderSearch
    {
        $info = is_array($id) ? $id : CompositeIdentifierParser::parse((string) $id);
        $provider = $this->getProvider((string) $info['code']);
        if (null === $provider || !$this->configuration->isActive($provider)) {
            return null;
        }

        if ('get' === $operationName) {
            $configurator = new ProviderConfigurator($this->configuration, null, null, null);
            $configurator->configure($provider);
            $results = $provider->searchIsbn($info['isbn']);

            if (0 === count($results)) {
                return null;
            }

            return new ProviderSearch($info['code'], $info['isbn'], array_map(function (SearchResultInterface $searchResult) {
                return ProviderSearchResult::create($searchResult);
            }, $results));
        }

        return null;
    }

    /**
     * @param array<mixed> $context
     */
    public function supports(string $resourceClass, ?string $operationName = null, array $context = []): bool
    {
        return ProviderSearch::class === $resourceClass && 'get' === $operationName && 'item' === $context['operation_type'];
    }

    private function getProvider(string $code): ?ProviderInterface
    {
        foreach ($this->providers as $provider) {
            if ($provider->getCode() === $code) {
                return $provider;
            }
        }

        return null;
    }
}
