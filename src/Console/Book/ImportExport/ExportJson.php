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

namespace App\Console\Book\ImportExport;

use App\Console\Book\OnBooksCommand;
use App\Doctrine\BookInjectionListener;
use App\Entity\Book;
use App\Repository\BookRepository;
use App\Worker\BookCreator;
use function assert;
use function fclose;
use function fopen;
use function fwrite;
use function is_string;
use function json_encode;
use function sprintf;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\ConsoleOutputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Filesystem\Exception\InvalidArgumentException;

class ExportJson extends OnBooksCommand
{
    private const CONSOLE = 'php://stdout';

    /** @var BookCreator */
    protected $bookCreator;

    /**
     * @var resource
     * @psalm-var resource|closed-resource
     * @psalm-suppress PropertyNotSetInConstructor
     */
    private $handle;

    /** @var int */
    private $count = 0;

    /** @var int */
    private $index = 0;

    public function __construct(BookRepository $bookRepository, BookCreator $bookCreator, BookInjectionListener $injector)
    {
        $this->bookCreator = $bookCreator;
        $injector->setDisableInjection(true);
        parent::__construct($bookRepository);
    }

    protected function configure(): void
    {
        $this
            ->setName('book:export:json')
            ->addArgument('path', InputArgument::OPTIONAL, 'Path of the JSON file to export', self::CONSOLE);
    }

    protected function createStyle(InputInterface $input, OutputInterface $output): SymfonyStyle
    {
        $path = $input->getArgument('path');
        assert(is_string($path));

        if (self::CONSOLE === $path && $output instanceof ConsoleOutputInterface) {
            return new SymfonyStyle($input, $output->getErrorOutput());
        }

        return parent::createStyle($input, $output);
    }

    /**
     * @phan-suppress PhanUnusedProtectedMethodParameter
     */
    protected function executeOnBook(InputInterface $input, OutputInterface $output, SymfonyStyle $style, Book $book): void
    {
        $this->index++;
        fwrite($this->handle, json_encode($this->bookCreator->bookToArray($book)) ?: '{}');
        if (!$this->isLast()) {
            fwrite($this->handle, ',');
        }
    }

    /**
     * @phan-suppress PhanUnusedProtectedMethodParameter
     */
    protected function beforeExecute(InputInterface $input, OutputInterface $output, SymfonyStyle $style, int $bookCount): void
    {
        parent::beforeExecute($input, $output, $style, $bookCount);
        $path = $input->getArgument('path');
        assert(is_string($path));

        $style->title(sprintf('Exporting to <info>%s</info>', $path));

        $handle = fopen($path, 'wb+');

        if (false === $handle) {
            throw new InvalidArgumentException();
        }
        $this->handle = $handle;

        fwrite($this->handle, '[');

        $this->count = $bookCount;
    }

    /**
     * @phan-suppress PhanUnusedProtectedMethodParameter
     */
    protected function afterExecute(InputInterface $input, OutputInterface $output, SymfonyStyle $style): void
    {
        fwrite($this->handle, ']');
        fclose($this->handle);

        $style->success('Export OK');
        parent::afterExecute($input, $output, $style);
    }

    private function isLast(): bool
    {
        return $this->index === $this->count;
    }
}
