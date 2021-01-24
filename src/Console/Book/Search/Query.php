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

use App\Entity\Book;
use function array_chunk;
use function array_combine;
use function array_map;
use function array_merge;
use function assert;
use function count;
use function is_string;
use MacFJA\RediSearch\Helper\PaginatedResult;
use MacFJA\RediSearch\Integration\ObjectManager;
use MacFJA\RediSearch\Search\Result;
use function range;
use function sprintf;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\ConsoleOutputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

class Query extends Command
{
    /** @var ObjectManager */
    private $objectManager;

    public function __construct(ObjectManager $objectManager)
    {
        parent::__construct();
        $this->objectManager = $objectManager;
    }

    protected function configure(): void
    {
        $this->setName('book:search:query')
            ->addArgument('query')
            ->setDescription('Run a search query');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $style = new SymfonyStyle($input, $output);

        $query = $input->getArgument('query');
        assert(is_string($query));

        $source = $this->objectManager->getSearchBuilder(Book::class)
            ->withQuery($query);

        if ($output instanceof ConsoleOutputInterface) {
            $page = 1;

            $sectionOutput = $output->section();
            $list = new SymfonyStyle($input, $sectionOutput);
            $list->setDecorated(true);
            do {
                $search = clone $source;
                $search->withResultOffset(($page - 1) * 10);
                $search->withResultLimit(10);
                $result = $search->execute();

                $sectionOutput->clear();
                $option = $this->renderPagination($result, $list);
                switch ($option) {
                    case 'p':
                        $page--;

                        break;
                    case 'n':
                        $page++;

                        break;
                    case 'q':
                        return 0;
                    default:
                        $page = (int) $option;
                }
            } while (!('q' === $option));

            return 0;
        }

        $result = $source->withResultOffset(0)->withResultLimit(40)->execute();

        $byTwo = array_chunk($result->getItems(), 10);
        foreach ($byTwo as $books) {
            $this->renderBook($books, $style);
        }

        if ($result->getTotalCount() > 0) {
            $style->success(sprintf(
                'Found %d books that match your query (%d displayed)',
                $result->getTotalCount(),
                count($result->getItems())
            ));

            return 0;
        }

        $style->warning('No books match your query');

        return 1;
    }

    /**
     * @param PaginatedResult<Result> $paginatedResult
     */
    private function renderPagination(PaginatedResult $paginatedResult, SymfonyStyle $style): string
    {
        $style->section(sprintf(
            'Page <info>%d</info> of <comment>%d</comment> (<info>%d</info> books in totals)',
            $paginatedResult->getPageNumber(),
            $paginatedResult->getPageCount(),
            $paginatedResult->getTotalCount()
        ));
        $this->renderBook($paginatedResult->getItems(), $style);

        $options = [];
        if ($paginatedResult->getPageCount() > 1) {
            if ($paginatedResult->havePreviousPage()) {
                $options['p'] = 'previous';
            }
            $options = array_merge($options, array_combine(
                range(1, $paginatedResult->getPageCount()),
                range(1, $paginatedResult->getPageCount())
            ) ?: []);
            if ($paginatedResult->haveNextPage()) {
                $options['n'] = 'next';
            }
            $options['q'] = 'quit';

            return $style->choice('Go to page', $options, 'q');
        }

        return 'q';
    }

    /**
     * @param array<Result> $books
     */
    private function renderBook(array $books, SymfonyStyle $style): void
    {
        $tableData = array_map(function (Result $book) {
            return [
                $book->getFields()['title'],
                $book->getFields()['isbn'],
                $book->getFields()['series'] ?? '-',
                $book->getFields()['authors'] ?? '-',
                $book->getFields()['owner'],
            ];
        }, $books);
        $style->table(['Title', 'ISBN', 'Series', 'Authors', 'Owner'], $tableData);
    }
}
