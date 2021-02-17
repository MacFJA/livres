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

namespace App\Model\Output;

use ApiPlatform\Core\Annotation\ApiProperty;
use DateTimeInterface;

class ProviderSearchResultData
{
    /** @ApiProperty(readable=true)
     * @var string
     */
    private $fusion;

    /** @ApiProperty(readable=true)
     *@var string
     */
    private $type;

    /**
     * @ApiProperty(readable=true)
     *
     * @var string
     */
    private $key;

    /**
     * @ApiProperty(readable=true)
     *
     * @var string
     */
    private $label;

    /**
     * @ApiProperty(readable=true)
     *
     * @var null|array<mixed>|DateTimeInterface|float|int|string
     */
    private $value;

    /**
     * @param null|array<mixed>|DateTimeInterface|float|int|string $value
     */
    public function __construct(string $fusion, string $type, string $key, string $label, $value)
    {
        $this->fusion = $fusion;
        $this->type = $type;
        $this->key = $key;
        $this->label = $label;
        $this->value = $value;
    }

    public function getFusion(): string
    {
        return $this->fusion;
    }

    public function getType(): string
    {
        return $this->type;
    }

    public function getKey(): string
    {
        return $this->key;
    }

    public function getLabel(): string
    {
        return $this->label;
    }

    /**
     * @return null|array<mixed>|DateTimeInterface|float|int|string
     */
    public function getValue()
    {
        return $this->value;
    }
}
