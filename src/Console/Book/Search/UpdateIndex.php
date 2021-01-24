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
use App\Worker\Search\ObjectFactory;
use function assert;
use function is_string;
use function is_subclass_of;
use MacFJA\RediSearch\Integration\MappedClass;
use MacFJA\RediSearch\Integration\MappedClassProvider;
use MacFJA\RediSearch\Integration\ObjectManager;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

class UpdateIndex extends OnBooksCommand
{
    /** @var ObjectManager */
    private $objectManager;

    /** @var ObjectFactory */
    private $objectFactory;

    /** @var MappedClassProvider */
    private $provider;

    public function __construct(ObjectFactory $objectFactory, MappedClassProvider $provider, BookRepository $bookRepository, ObjectManager $objectManager)
    {
        parent::__construct($bookRepository);
        $this->objectManager = $objectManager;
        $this->objectFactory = $objectFactory;
        $this->provider = $provider;
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
     * @phan-suppress PhanUnusedProtectedMethodParameter
     */
    protected function executeOnBook(InputInterface $input, OutputInterface $output, SymfonyStyle $style, Book $book): void
    {
        $this->objectManager->addObjectInSearch($book);
    }

    protected function afterExecute(InputInterface $input, OutputInterface $output, SymfonyStyle $style): void
    {
        $style->success('Search index rebuild');
        parent::afterExecute($input, $output, $style);
    }

    /**
     * @phan-suppress PhanUnusedVariableCaughtException
     */
    private function recreateIndex(): void
    {
        /** @var class-string<MappedClass>|null $mapped */
        $mapped = $this->provider->getStaticMappedClass(Book::class);
        assert(is_string($mapped) && is_subclass_of($mapped, MappedClass::class));
        $this->objectFactory->getIndex($mapped::getRSIndexName())
            ->delete(true);
        $this->objectManager->createIndex(Book::class);
    }
}
