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

use function array_combine;
use function array_keys;
use function array_map;
use function count;
use InvalidArgumentException;

class PropertyFilter implements \ApiPlatform\Core\Api\FilterInterface
{
    /** @var array<string> */
    private $properties;

    /**
     * @param array<string,mixed> $properties
     */
    public function __construct(array $properties = [])
    {
        if (0 === count($properties)) {
            throw new InvalidArgumentException();
        }
        $this->properties = array_keys($properties);
    }

    /**
     * @return array<array<mixed>>
     * @phan-suppress PhanUnusedPublicMethodParameter
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function getDescription(string $resourceClass): array
    {
        return array_combine($this->properties, array_map(function (string $property) {
            return [
                'property' => $property,
                'type' => 'string',
                'required' => true,
            ];
        }, $this->properties)) ?: [];
    }
}
