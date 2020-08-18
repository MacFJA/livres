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

namespace App\Worker\Search\Suggestion;

use App\Console\Book\Search\InvalidPayloadException;
use App\Entity\Book;
use App\Worker\Search\Suggestion;
use function array_filter;
use function array_shift;
use function assert;
use function count;
use function explode;
use function is_string;
use function json_encode;
use const JSON_THROW_ON_ERROR;
use JsonException;
use function max;
use function md5;
use function reset;

trait BookSuggestionIndexer
{
    /**
     * @param array<string> $tags
     * @param array<mixed>  $additionalPayload
     */
    public function addTags(array $tags, string $type, float $partScore = 0.75, array $additionalPayload = []): void
    {
        foreach ($tags as $tag) {
            $this->addWords($tag, $type, $partScore, $additionalPayload + ['kind' => 'tag']);
        }
    }

    abstract public function saveSuggestion(Suggestion $suggestion): void;

    abstract public function getExistingSuggestion(string $data): ?Suggestion;

    /**
     * @param array<mixed> $additionalPayload
     */
    public function addWords(string $words, string $type, float $partScore = 0.75, array $additionalPayload = []): void
    {
        $this->addSuggestion($words, 1.0, ['type' => $type] + $additionalPayload);

        $parts = explode(' ', $words);
        if (count($parts) < 2) {
            return;
        }
        array_shift($parts);
        foreach ($parts as $part) {
            $this->addSuggestion($part, $partScore, ['type' => $type, 'full' => $words] + $additionalPayload);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function addToIndex(Book $book): void
    {
        $this->addWords($book->getTitle(), 'title', 0.5, ['bookId' => $book->getBookId()]);
        $this->addTags($book->getAuthors(), 'author');
        $this->addTags($book->getIllustrators(), 'illustrator');

        $this->addSuggestion($book->getSeries(), 1.0, ['type' => 'series']);

        $this->addSuggestion($book->getOwner(), 1.0, ['type' => 'owner']);

        foreach ($book->getGenres() as $genre) {
            $this->addSuggestion($genre, 1.0, ['type' => 'genre', 'kind' => 'tag']);
        }
        foreach ($book->getKeywords() as $keyword) {
            $this->addSuggestion($keyword, 1.0, ['type' => 'keyword', 'kind' => 'tag']);
        }
        $this->addSuggestion($book->getFormat(), 1.0, ['type' => 'format']);
    }

    /**
     * @param array<mixed> $payload
     *
     * @throws InvalidPayloadException
     */
    private function addSuggestion(?string $data, float $score = 1.0, $payload = []): void
    {
        if (null === $data) {
            return;
        }
        $suggestion = $this->getExistingSuggestion($data) ?? new Suggestion($data, $score, null);
        $suggestion->setScore(max($suggestion->getScore(), $score));

        try {
            $encodedPayload = json_encode($payload, JSON_THROW_ON_ERROR);
            assert(is_string($encodedPayload));
            $payloads = $suggestion->getArrayPayload();
            $payloads[md5($encodedPayload)] = $payload;
            $suggestion->setArrayPayload($payloads);
            $this->saveSuggestion($suggestion);
        } catch (JsonException $exception) {
            throw new InvalidPayloadException('Payload cannot be encoded into a JSON', 0, $exception);
        }
    }

    private function getSuggestionFromRedis(\Ehann\RediSearch\Suggestion $index, string $data): ?Suggestion
    {
        $suggestions = $index->get($data, false, true, -1, true);

        $suggestions = Suggestion::listFromRawRedis($suggestions, true, true);
        $suggestions = array_filter($suggestions, function (Suggestion $suggestion) use ($data) {
            return $suggestion->getValue() === $data;
        });

        if (1 === count($suggestions)) {
            $item = reset($suggestions);
            assert($item instanceof Suggestion);

            return $item;
        }

        return null;
    }
}
