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
use function assert;
use function count;
use DateTimeInterface;
use Doctrine\ORM\EntityManagerInterface;
use EmptyIterator;
use function is_array;
use MacFJA\BookRetriever\Pool;
use MacFJA\BookRetriever\ProviderConfigurationInterface;
use MacFJA\BookRetriever\ProviderInterface;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Traversable;

/**
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class BookCompleter extends EditBooksCommand
{
    use GetProvidersTrait;

    /** @var iterable<ProviderInterface> */
    private $providers;

    /** @var ProviderConfigurationInterface */
    private $configuration;

    /** @var Pool */
    private $pool;

    /**
     * CoverCompleter constructor.
     *
     * @param iterable<ProviderInterface>|Traversable<ProviderInterface> $providers
     */
    public function __construct(BookRepository $bookRepository, EntityManagerInterface $objectManager, ProviderConfigurationInterface $configuration, iterable $providers)
    {
        $this->configuration = $configuration;
        $this->providers = $providers;
        $this->pool = new Pool(new EmptyIterator(), $this->configuration);
        parent::__construct($bookRepository, $objectManager);
    }

    protected function configure(): void
    {
        $this
            ->setName('book:data:completer')
            ->addArgument('provider', InputArgument::IS_ARRAY | InputArgument::OPTIONAL, 'List of providers (code) to use', $this->getAllProvidersCode($this->providers))
            ->setDescription('Complete books');
        parent::configure();
    }

    /**
     * @phan-suppress PhanUnusedProtectedMethodParameter
     */
    protected function beforeExecute(InputInterface $input, OutputInterface $output, SymfonyStyle $style, int $bookCount): void
    {
        $names = $input->getArgument('provider');
        assert(is_array($names) || null === $names);
        $this->pool = $this->getProvidersPool($names ?? [], $this->configuration, $this->providers);
        parent::beforeExecute($input, $output, $style, $bookCount);
    }

    /**
     * @phan-suppress PhanUnusedProtectedMethodParameter
     */
    protected function editBook(InputInterface $input, OutputInterface $output, SymfonyStyle $style, Book $book): bool
    {
        $results = $this->pool->searchIsbn($book->getIsbn());

        if (0 === count($results)) {
            return false;
        }

        $update = 0;
        /** @var \MacFJA\BookRetriever\SearchResultInterface $result */
        foreach ($results as $result) {
            $update |= (int) $this->updateField($book, 'title', $result->getTitle());
            $update |= (int) $this->updateField($book, 'cover', $result->getCover());
            $update |= (int) $this->updateField($book, 'keywords', $result->getKeywords());
            $update |= (int) $this->updateField($book, 'dimension', $result->getDimension());
            $update |= (int) $this->updateField($book, 'format', $result->getFormat());
            $update |= (int) $this->updateField($book, 'publicationDate', $result->getPublicationDate());
            $update |= (int) $this->updateField($book, 'genres', $result->getGenres());
            $update |= (int) $this->updateField($book, 'illustrators', $result->getIllustrators());
            $update |= (int) $this->updateField($book, 'series', $result->getSeries());
            $update |= (int) $this->updateField($book, 'pages', $result->getPages());
            $update |= (int) $this->updateField($book, 'authors', $result->getAuthors());
            $update |= (int) $this->updateAdditional($book, 'translators', $result->getTranslators());
            foreach ($result->getAdditional() as $field => $value) {
                $update |= (int) $this->updateAdditional($book, $field, $value);
            }
        }

        return (bool) $update;
    }

    /**
     * @param array<mixed>|DateTimeInterface|float|int|mixed|string $value
     */
    private function updateField(Book $book, string $field, $value): bool
    {
        $metadata = $this->objectManager->getClassMetadata(Book::class);

        if (!$metadata->hasField($field)) {
            return false;
        }

        $original = $metadata->getFieldValue($book, $field);
        if (null === $original && !(null === $value)) {
            $metadata->setFieldValue($book, $field, $value);

            return true;
        }

        if (is_array($original) && 0 === count($original) && is_array($value) && count($value) > 0) {
            $metadata->setFieldValue($book, $field, $value);

            return true;
        }

        return false;
    }

    /**
     * @param array<mixed> $value
     */
    private function updateAdditional(Book $book, string $field, array $value): bool
    {
        $additional = $book->getAdditional();
        $original = $additional[$field] ?? [];

        if (is_array($original) && 0 === count($original) && count($value) > 0) {
            $additional[$field] = $value;
            $book->setAdditional($additional);

            return true;
        }

        return false;
    }
}
