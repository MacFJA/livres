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

namespace App\Console\Book;

use function array_filter;
use function array_map;
use ArrayIterator;
use function assert;
use function iterator_to_array;
use MacFJA\BookRetriever\Pool;
use MacFJA\BookRetriever\ProviderConfigurationInterface;
use MacFJA\BookRetriever\ProviderInterface;
use Traversable;

trait GetProvidersTrait
{
    /**
     * @param array<string>                                              $providersName
     * @param iterable<ProviderInterface>|Traversable<ProviderInterface> $installedProviders
     */
    private function getProvidersPool(array $providersName, ProviderConfigurationInterface $configuration, iterable $installedProviders): Pool
    {
        $providers = array_map(function (string $providerCode) use ($installedProviders): ?ProviderInterface {
            foreach ($installedProviders as $provider) {
                if ($provider->getCode() === $providerCode) {
                    return $provider;
                }
            }

            return null;
        }, $providersName);
        $providers = array_filter($providers);
        if (empty($providers)) {
            assert($installedProviders instanceof Traversable);
            $providers = iterator_to_array($installedProviders);
        }

        return new Pool(new ArrayIterator($providers), $configuration);
    }

    /**
     * @param iterable<ProviderInterface>|Traversable<ProviderInterface> $installedProviders
     *
     * @return array<string>
     */
    private function getAllProvidersCode(iterable $installedProviders): array
    {
        $names = [];
        foreach ($installedProviders as $provider) {
            if ('__pool__' === $provider->getCode()) {
                continue;
            }
            $names[] = $provider->getCode();
        }

        return $names;
    }
}
