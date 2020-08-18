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
use function array_map;
use function array_shift;
use function asort;
use function assert;
use function count;
use Doctrine\ORM\EntityManagerInterface;
use EmptyIterator;
use function end;
use Imagine\Gd\Imagine;
use function is_array;
use function is_string;
use function key;
use MacFJA\BookRetriever\Pool;
use MacFJA\BookRetriever\ProviderConfigurationInterface;
use MacFJA\BookRetriever\ProviderInterface;
use MacFJA\BookRetriever\SearchResultInterface;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Traversable;

/**
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class CoverCompleter extends EditBooksCommand
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
            ->setName('book:cover:completer')
            ->addArgument('provider', InputArgument::IS_ARRAY | InputArgument::OPTIONAL, 'List of providers (code) to use', $this->getAllProvidersCode($this->providers))
            ->addOption('prefer-largest', null, InputOption::VALUE_NONE)
            ->addOption('only-missing', null, InputOption::VALUE_NONE);
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
        if (true === $input->getOption('only-missing') && is_string($book->getCover())) {
            return false;
        }
        $results = $this->pool->searchIsbn($book->getIsbn());

        $images = array_map(function (SearchResultInterface $result) {
            return $result->getCover();
        }, $results);
        $images = array_filter($images);

        if (0 === count($images)) {
            return false;
        }

        if (!$input->getOption('prefer-largest')) {
            $image = array_shift($images);
            assert(is_string($image));
            $book->setCover($image);

            return true;
        }

        $allSize = [];
        foreach ($images as $imagePath) {
            $image = (new Imagine())->open($imagePath);
            $size = $image->getSize()->getHeight() * $image->getSize()->getWidth();
            $allSize[$imagePath] = $size;
        }

        asort($allSize);
        end($allSize);
        $coverPath = key($allSize);
        assert(is_string($coverPath));
        $book->setCover($coverPath);

        return true;
    }
}
