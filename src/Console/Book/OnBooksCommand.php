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

use App\Entity\Book;
use App\Repository\BookRepository;
use function array_filter;
use function array_keys;
use function array_map;
use function array_merge;
use function assert;
use function count;
use DateTimeInterface;
use function explode;
use function implode;
use function is_string;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

abstract class OnBooksCommand extends Command
{
    /** @var BookRepository */
    private $bookRepository;

    public function __construct(BookRepository $bookRepository)
    {
        $this->bookRepository = $bookRepository;
        parent::__construct();
    }

    protected function createStyle(InputInterface $input, OutputInterface $output): SymfonyStyle
    {
        return new SymfonyStyle($input, $output);
    }

    protected function configure(): void
    {
        $this->addOption('books-id', null, InputOption::VALUE_REQUIRED, 'List of book id to handle (Leave empty to match all books)', '');
        parent::configure();
    }

    /**
     * @return int
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $style = $this->createStyle($input, $output);

        $booksId = $input->getOption('books-id') ?? '';
        assert(is_string($booksId));
        $booksId = array_filter(explode(',', $booksId));

        $criteria = [];
        if (count($booksId) > 0) {
            $criteria = ['bookId' => $booksId];
        }

        $allBooks = $this->bookRepository->findBy($criteria);
        $count = $this->bookRepository->count($criteria);

        $this->beforeExecute($input, $output, $style, $count);

        $progress = $style->createProgressBar($count);

        foreach ($allBooks as $book) {
            $this->executeOnBook($input, $output, $style, $book);

            $progress->advance();
        }
        $progress->finish();
        $style->newLine(2);

        $this->afterExecute($input, $output, $style);

        return 0;
    }

    abstract protected function executeOnBook(InputInterface $input, OutputInterface $output, SymfonyStyle $style, Book $book): void;

    /**
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     * @phan-suppress PhanUnusedProtectedMethodParameter
     */
    protected function beforeExecute(InputInterface $input, OutputInterface $output, SymfonyStyle $style, int $bookCount): void
    {
    }

    /**
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     * @phan-suppress PhanUnusedProtectedMethodParameter
     */
    protected function afterExecute(InputInterface $input, OutputInterface $output, SymfonyStyle $style): void
    {
    }

    protected function renderBook(Book $book, OutputInterface $output): Table
    {
        $table = new Table($output);
        $table->setHorizontal();

        $headers = array_merge(
            ['Title', 'Cover', 'Keywords', 'Dimension', 'Format', 'Publication Date', 'Genres', 'Illustrators', 'Series', 'Pages', 'Authors'],
            array_map('ucfirst', array_map('strval', array_keys($book->getAdditional())))
        );

        $publicationDate = $book->getPublicationDate();
        $row = [array_merge([
            $book->getTitle(),
            $book->getCover(),
            implode(', ', $book->getKeywords()),
            $book->getDimension(),
            $book->getFormat(),
            $publicationDate instanceof DateTimeInterface ? $publicationDate->format('r') : '',
            implode(',', $book->getGenres()),
            implode(',', $book->getIllustrators()),
            $book->getSeries(),
            $book->getPages(),
            implode(',', $book->getAuthors()),
        ], array_map(function (array $data) {
            return implode(', ', $data);
        }, $book->getAdditional()))];

        $table->setHeaders($headers);
        $table->setRows($row);

        return $table;
    }
}
