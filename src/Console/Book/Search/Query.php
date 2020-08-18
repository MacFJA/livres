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
use function array_chunk;
use function array_map;
use function assert;
use function count;
use function is_string;
use function sprintf;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

class Query extends Command
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
        $this->setName('book:search:query')
            ->addArgument('query')
            ->setDescription('Run a search query');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $style = new SymfonyStyle($input, $output);

        $query = $input->getArgument('query');
        assert(is_string($query));

        /** @var array<array<mixed>> $result */
        $result = $this->bookSearch->getSearch($query);

        $byTwo = array_chunk($result, 10);
        foreach ($byTwo as $books) {
            $this->renderBook($books, $style);
        }

        if (count($result) > 0) {
            $style->success(sprintf('Found %d books that match your query', count($result)));

            return 0;
        }

        $style->warning('No books match your query');

        return 1;
    }

    /**
     * @param array<array<mixed>> $books
     * @phan-param array<array{title:string,isbn:string,series:string,author:string,owner:string}> $books
     */
    private function renderBook(array $books, SymfonyStyle $style): void
    {
        $tableData = array_map(function ($book) {
            return [
                $book['title'],
                $book['isbn'],
                $book['series'] ?? '-',
                $book['author'],
                $book['owner'],
            ];
        }, $books);
        $style->table(['Title', 'ISBN', 'Series', 'Authors', 'Owner'], $tableData);
    }
}
