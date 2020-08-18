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

use App\Worker\Search\Suggestion as AppSuggestion;
use function array_key_exists;
use function count;
use Ehann\RediSearch\Suggestion;
use Flintstone\Flintstone;
use function is_callable;

class BatchIndexer implements SuggestionIndexer
{
    use BookSuggestionIndexer;

    /** @var Suggestion */
    private $suggestion;

    /** @var array<AppSuggestion> */
    private $suggestions = [];

    /** @var bool */
    private $isPurged = false;

    /** @var Flintstone */
    private $flintstone;

    public function __construct(Suggestion $suggestion, Flintstone $flintstone)
    {
        $this->suggestion = $suggestion;
        $this->flintstone = $flintstone;
    }

    public function setPurged(bool $isPurged): void
    {
        $this->isPurged = $isPurged;
    }

    public function getExistingSuggestion(string $data): ?AppSuggestion
    {
        if (array_key_exists($data, $this->suggestions)) {
            return $this->suggestions[$data];
        }

        if (true === $this->isPurged) {
            return null;
        }

        return $this->getSuggestionFromRedis($this->suggestion, $data);
    }

    public function saveSuggestion(AppSuggestion $suggestion): void
    {
        $this->suggestions[$suggestion->getValue()] = $suggestion;
    }

    public function saveBatch(?callable $beforeSave = null, ?callable $afterSave = null): void
    {
        /** @var AppSuggestion $suggestion */
        foreach ($this->suggestions as $suggestion) {
            if (is_callable($beforeSave)) {
                $suggestion = $beforeSave($suggestion);
            }
            if ($suggestion instanceof AppSuggestion) {
                /**
                 * @psalm-suppress InvalidArgument
                 */
                // @phpstan-ignore-next-line
                $this->suggestion->add(
                    $suggestion->getValue(),
                    $suggestion->getScore(),
                    false,
                    $suggestion->getPayload()// @phan-suppress-current-line PhanTypeMismatchArgumentProbablyReal
                );
            }
            if (is_callable($afterSave)) {
                $afterSave($suggestion);
            }
        }
        $this->flintstone->set(AppSuggestion::IS_DIRTY_CONFIG_NAME, false === $this->isPurged);
    }

    public function getBatchSize(): int
    {
        return count($this->suggestions);
    }

    public function reset(): void
    {
        $this->suggestions = [];
    }
}
