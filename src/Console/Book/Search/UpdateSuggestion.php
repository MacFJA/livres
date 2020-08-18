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
use App\Worker\Search\Suggestion\BatchIndexer;
use Ehann\RedisRaw\RedisRawClientInterface;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

class UpdateSuggestion extends OnBooksCommand
{
    /** @var RedisRawClientInterface */
    private $redisClient;

    /** @var string */
    private $suggestionIndexName;

    /** @var BatchIndexer */
    private $indexer;

    public function __construct(BookRepository $bookRepository, RedisRawClientInterface $redisClient, BatchIndexer $indexer, string $suggestionIndexName)
    {
        parent::__construct($bookRepository);
        $this->redisClient = $redisClient;
        $this->suggestionIndexName = $suggestionIndexName;
        $this->indexer = $indexer;
    }

    protected function configure(): void
    {
        $this->setName('book:search:rebuild-suggestion')
            ->setDescription('Reindex the search engine suggestion');
        parent::configure();
    }

    /**
     * @phan-suppress PhanUnusedProtectedMethodParameter
     */
    protected function beforeExecute(InputInterface $input, OutputInterface $output, SymfonyStyle $style, int $bookCount): void
    {
        $style->section('Clear suggestion index');
        $this->redisClient->rawCommand('DEL', [$this->suggestionIndexName]);
        $this->indexer->setPurged(true);
        $style->success('Clear done');

        $style->section('Parse all books');
    }

    /**
     * @phan-suppress PhanUnusedProtectedMethodParameter
     */
    protected function executeOnBook(InputInterface $input, OutputInterface $output, SymfonyStyle $style, Book $book): void
    {
        $this->indexer->addToIndex($book);
    }

    /**
     * @phan-suppress PhanUnusedProtectedMethodParameter
     * @phan-suppress PhanTypeMismatchArgument
     */
    protected function afterExecute(InputInterface $input, OutputInterface $output, SymfonyStyle $style): void
    {
        $style->section('Add all suggestions');
        $style->progressStart($this->indexer->getBatchSize());
        $this->indexer->saveBatch(null, function () use ($style) {
            $style->progressAdvance();
        });
        $style->progressFinish();
        $style->success('Insertion done');
        parent::afterExecute($input, $output, $style);
    }
}
