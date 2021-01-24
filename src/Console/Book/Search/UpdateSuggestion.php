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
use App\EventSubscriber\RemoveDocumentSubscriber;
use App\Repository\BookRepository;
use Flintstone\Flintstone;
use function is_subclass_of;
use MacFJA\RediSearch\Integration\MappedClass;
use MacFJA\RediSearch\Integration\MappedClassProvider;
use MacFJA\RediSearch\Integration\ObjectManager;
use Predis\Client;
use RuntimeException;
use function sprintf;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

class UpdateSuggestion extends OnBooksCommand
{
    /**
     * @phpstan-var Client<Client>
     * @psalm-var Client
     *
     * @var Client
     */
    private $redisClient;

    /** @var ObjectManager */
    private $objectManager;

    /** @var Flintstone */
    private $flintstone;

    /** @var MappedClassProvider */
    private $provider;

    /**
     * @phpstan-param Client<Client> $redisClient
     * @psalm-param Client $redisClient
     */
    public function __construct(Flintstone $flintstone, MappedClassProvider $provider, ObjectManager $objectManager, BookRepository $bookRepository, Client $redisClient)
    {
        parent::__construct($bookRepository);
        $this->redisClient = $redisClient;
        $this->objectManager = $objectManager;
        $this->flintstone = $flintstone;
        $this->provider = $provider;
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
        /** @var class-string<MappedClass>|null $mapped */
        $mapped = $this->provider->getStaticMappedClass(Book::class);
        if (null === $mapped || !is_subclass_of($mapped, MappedClass::class)) {
            throw new RuntimeException(sprintf(
                'The entity %s is not mapped into RediSearch',
                Book::class
            ));
        }

        foreach ($mapped::getRSSuggestionGroups() as $group) {
            $this->redisClient->del($group);
        }
        $this->flintstone->set(RemoveDocumentSubscriber::SUGGESTIONS_DIRTY, 'no');
        $style->success('Clear done');

        $style->section('Parse all books');
    }

    /**
     * @phan-suppress PhanUnusedProtectedMethodParameter
     */
    protected function executeOnBook(InputInterface $input, OutputInterface $output, SymfonyStyle $style, Book $book): void
    {
        $this->objectManager->addObjectInSuggestion($book);
    }

    /**
     * @phan-suppress PhanUnusedProtectedMethodParameter
     * @phan-suppress PhanTypeMismatchArgument
     */
    protected function afterExecute(InputInterface $input, OutputInterface $output, SymfonyStyle $style): void
    {
        $style->success('Insertion done');
        parent::afterExecute($input, $output, $style);
    }
}
