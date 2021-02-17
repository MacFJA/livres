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

namespace App\Model;

use ApiPlatform\Core\Annotation\ApiProperty;
use ApiPlatform\Core\Annotation\ApiResource;
use App\Model\Output\ProviderSearchResult;

/**
 * @ApiResource(
 *     compositeIdentifier=false,
 *     collectionOperations={},
 *     itemOperations={"get"={
 *         "security"="is_granted('ROLE_CAN_ADD')",
 *     }}
 * )
 */
class ProviderSearch
{
    /**
     * @ApiProperty(identifier=true)
     *
     * @var string
     */
    private $code;

    /**
     * @ApiProperty(identifier=true)
     *
     * @var string
     */
    private $isbn;

    /**
     * @ApiProperty(readable=true)
     *
     * @var array<ProviderSearchResult>
     */
    private $results;

    /**
     * @param array<ProviderSearchResult> $results
     */
    public function __construct(string $code, string $isbn, array $results)
    {
        $this->code = $code;
        $this->isbn = $isbn;
        $this->results = $results;
    }

    public function getCode(): string
    {
        return $this->code;
    }

    public function getIsbn(): string
    {
        return $this->isbn;
    }

    /**
     * @return array<ProviderSearchResult>
     */
    public function getResults(): array
    {
        return $this->results;
    }
}
