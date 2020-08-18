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

namespace App\Worker\Search;

use function array_chunk;
use function array_map;
use function array_shift;
use function assert;
use function count;
use InvalidArgumentException;
use function is_string;
use function json_decode;
use function json_encode;
use const JSON_THROW_ON_ERROR;
use JsonException;
use JsonSerializable;

class Suggestion implements JsonSerializable
{
    public const DEFAULT_SCORE = 1.0;

    public const EMPTY_SCORE = 0.0;

    public const IS_DIRTY_CONFIG_NAME = 'suggestion_is_dirty';

    /** @var string */
    private $value;

    /** @var float */
    private $score;

    /** @var null|string */
    private $payload;

    public function __construct(string $value, float $score = self::DEFAULT_SCORE, ?string $payload = null)
    {
        $this->value = $value;
        $this->score = $score;
        $this->payload = $payload;
    }

    public function getValue(): string
    {
        return $this->value;
    }

    public function setValue(string $value): void
    {
        $this->value = $value;
    }

    public function getScore(): float
    {
        return $this->score;
    }

    public function setScore(float $score): void
    {
        $this->score = $score;
    }

    public function getPayload(): ?string
    {
        return $this->payload;
    }

    public function setPayload(string $payload): void
    {
        $this->payload = $payload;
    }

    /**
     * @param array<mixed> $payload
     *
     * @throws JsonException
     */
    public function setArrayPayload(array $payload): void
    {
        $this->setPayload(json_encode($payload, JSON_THROW_ON_ERROR) ?: '[]');
    }

    /**
     * @throws JsonException
     *
     * @return array<mixed>
     */
    public function getArrayPayload(): array
    {
        if (null === $this->payload) {
            return [];
        }

        return (array) json_decode($this->payload, true, 512, JSON_THROW_ON_ERROR);
    }

    /**
     * @param array<float|string> $data
     */
    public static function fromRawRedis(array $data, bool $withPayload = true, bool $withScore = false): Suggestion
    {
        $value = array_shift($data);
        $score = self::EMPTY_SCORE;
        $payload = null;
        if (true === $withScore) {
            if (0 === count($data)) {
                throw new InvalidArgumentException();
            }
            $score = array_shift($data);
        }
        if (true === $withPayload) {
            if (0 === count($data)) {
                throw new InvalidArgumentException();
            }
            $payload = array_shift($data);
        }
        if (count($data) > 0) {
            throw new InvalidArgumentException();
        }

        assert(is_string($payload) || null === $payload);

        return new Suggestion((string) $value, (float) $score, $payload);
    }

    /**
     * @param array<float|string> $data
     *
     * @return array<Suggestion>
     */
    public static function listFromRawRedis(array $data, bool $withPayload = true, bool $withScore = false): array
    {
        $size = 1;
        if (true === $withPayload) {
            $size++;
        }
        if (true === $withScore) {
            $size++;
        }
        $suggestions = array_chunk($data, $size, false);

        return array_map(function (array $grouped) use ($withPayload, $withScore) {
            return self::fromRawRedis($grouped, $withPayload, $withScore);
        }, $suggestions);
    }

    /**
     * Specify data which should be serialized to JSON.
     *
     * @link https://php.net/manual/en/jsonserializable.jsonserialize.php
     *
     * @return mixed data which can be serialized by <b>json_encode</b>,
     *               which is a value of any type other than a resource.
     *
     * @since 5.4
     */
    public function jsonSerialize()
    {
        return [
            'value' => $this->getValue(),
            'score' => $this->getScore(),
            'payload' => $this->getArrayPayload(),
        ];
    }
}
