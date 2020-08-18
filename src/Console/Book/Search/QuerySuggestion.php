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

namespace App\Console\Book\Search;

use App\Worker\BookSearch;
use App\Worker\Search\Suggestion;
use function array_key_exists;
use function array_keys;
use function array_map;
use function array_reduce;
use function array_values;
use function assert;
use function implode;
use function is_string;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use function ucfirst;

class QuerySuggestion extends Command
{
    /** @var BookSearch */
    private $bookSearch;

    public function __construct(BookSearch $bookSearch)
    {
        parent::__construct();
        $this->bookSearch = $bookSearch;
    }

    protected function configure(): void
    {
        $this->setName('book:search:suggestion')
            ->addArgument('query')
            ->addOption('group', 'g', InputOption::VALUE_NONE)
            ->setDescription('Run a search query');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $style = new SymfonyStyle($input, $output);

        $query = $input->getArgument('query');
        assert(is_string($query));

        $suggestions = $this->bookSearch->getSuggestions($query);

        if (true === $input->getOption('group')) {
            $grouped = array_reduce($suggestions, function (array $groups, Suggestion $suggestion) {
                $payload = $suggestion->getArrayPayload();
                $type = $payload['type'] ?? 'unknown';
                if (!array_key_exists($type, $groups)) {
                    $groups[$type] = [];
                }
                $groups[$type][] = $suggestion;

                return $groups;
            }, []);

            foreach ($grouped as $type => $groupedSuggestions) {
                $style->section(ucfirst((string) $type));
                $this->displaySuggestion($style, $groupedSuggestions);
            }

            return 0;
        }

        $this->displaySuggestion($style, $suggestions);

        return 0;
    }

    /**
     * @param array<Suggestion> $suggestions
     */
    private function displaySuggestion(SymfonyStyle $style, array $suggestions): void
    {
        $displayable = array_map(function (Suggestion $suggestion) {
            $payload = $suggestion->getArrayPayload();
            $payload = implode(', ', array_map(function (string $payloadValue, $payloadKey) {
                return ucfirst((string) $payloadKey).': '.$payloadValue;
            }, array_values($payload), array_keys($payload)));

            return [$suggestion->getValue(), $payload];
        }, $suggestions);

        $style->table(['suggestion', 'payload'], $displayable);
    }
}
