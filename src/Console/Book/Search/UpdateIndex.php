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

use App\Console\Book\OnBooksCommand;
use App\Entity\Book;
use App\Repository\BookRepository;
use App\Worker\BookSearch;
use App\Worker\Search\Index\BookDocument;
use Ehann\RediSearch\Exceptions\FieldNotInSchemaException;
use Ehann\RediSearch\Exceptions\NoFieldsInIndexException;
use Ehann\RediSearch\Exceptions\UnknownIndexNameException;
use Ehann\RediSearch\Index;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

class UpdateIndex extends OnBooksCommand
{
    /** @var Index */
    private $index;

    /** @var BookSearch */
    private $bookSearch;

    public function __construct(BookRepository $bookRepository, Index $index, BookSearch $bookSearch)
    {
        parent::__construct($bookRepository);
        $this->index = $index;
        $this->bookSearch = $bookSearch;
    }

    protected function configure(): void
    {
        $this->setName('book:search:reindex')
            ->setDescription('Reindex the search engine index');
        parent::configure();
    }

    protected function beforeExecute(InputInterface $input, OutputInterface $output, SymfonyStyle $style, int $bookCount): void
    {
        parent::beforeExecute($input, $output, $style, $bookCount);
        $style->section('Clear search index');
        $this->recreateIndex();
        $style->success('Clear done');
        $style->section('Add book search data');
    }

    /**
     * @throws FieldNotInSchemaException
     * @phan-suppress PhanUnusedProtectedMethodParameter
     */
    protected function executeOnBook(InputInterface $input, OutputInterface $output, SymfonyStyle $style, Book $book): void
    {
        $this->bookSearch->addBookToIndex($book);
    }

    protected function afterExecute(InputInterface $input, OutputInterface $output, SymfonyStyle $style): void
    {
        $style->success('Search index rebuild');
        parent::afterExecute($input, $output, $style);
    }

    /**
     * @throws NoFieldsInIndexException
     * @phan-suppress PhanUnusedVariableCaughtException
     */
    private function recreateIndex(): void
    {
        try {
            $this->index->drop();
        } catch (UnknownIndexNameException $exception) {
            // Do nothing
        }

        BookDocument::createIndexDefinition($this->index);
    }
}
