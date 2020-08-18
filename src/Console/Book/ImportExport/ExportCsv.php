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
use function array_keys;
use function array_search;
use function assert;
use DateTime;
use function fclose;
use function fopen;
use function fputcsv;
use function is_string;
use function sprintf;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\ConsoleOutputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Filesystem\Exception\InvalidArgumentException;
use function uksort;

class ExportCsv extends OnBooksCommand
{
    private const CONSOLE = 'php://stdout';

    /** @var BookCreator */
    protected $bookCreator;

    /**
     * @var array<mixed>
     * @phan-var array{bookId:string,isbn:string,title:string,series:string,sortTitle:string,owner:string,addedAt:string,pages:int,publicationDate:string,format:string,dimension:string,cover:string,storage:string,authors:array<string>,illustrators:array<string>,genres:array<string>,keywords:array<string>,additional:array<mixed>}
     */
    private $default;

    /**
     * @var resource
     * @psalm-var resource|closed-resource
     * @psalm-suppress PropertyNotSetInConstructor
     */
    private $handle;

    public function __construct(BookRepository $bookRepository, BookCreator $bookCreator, BookInjectionListener $injector)
    {
        $this->bookCreator = $bookCreator;
        $injector->setDisableInjection(true);

        $this->default = [
            'bookId' => '',
            'isbn' => '',
            'title' => '',
            'series' => '',
            'sortTitle' => '',
            'owner' => 'nobody',
            'addedAt' => (new DateTime())->format('Y-m-d'),
            'pages' => 0,
            'publicationDate' => '',
            'format' => '',
            'dimension' => '',
            'cover' => '',
            'storage' => '',
            'authors' => [],
            'illustrators' => [],
            'genres' => [],
            'keywords' => [],
            'additional' => [],
        ];

        parent::__construct($bookRepository);
    }

    protected function configure(): void
    {
        $this
            ->setName('book:export:csv')
            ->addArgument('path', InputArgument::OPTIONAL, 'Path of the CSV file to export', self::CONSOLE);
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

    protected function beforeExecute(InputInterface $input, OutputInterface $output, SymfonyStyle $style, int $bookCount): void
    {
        $path = $input->getArgument('path');
        assert(is_string($path));

        if (self::CONSOLE === $path && $output instanceof ConsoleOutputInterface) {
            $style = new SymfonyStyle($input, $output->getErrorOutput());
        }

        $style->title(sprintf('Exporting to <info>%s</info>', $path));

        $handle = fopen($path, 'wb+');

        if (false === $handle) {
            throw new InvalidArgumentException();
        }
        $this->handle = $handle;

        fputcsv($this->handle, array_keys($this->default));

        parent::beforeExecute($input, $output, $style, $bookCount);
    }

    protected function afterExecute(InputInterface $input, OutputInterface $output, SymfonyStyle $style): void
    {
        parent::afterExecute($input, $output, $style);
        fclose($this->handle);
        $style->success('Export OK');
    }

    /**
     * @phan-suppress PhanUnusedProtectedMethodParameter
     */
    protected function executeOnBook(InputInterface $input, OutputInterface $output, SymfonyStyle $style, Book $book): void
    {
        $array = $this->bookCreator->bookToArray($book) + $this->default;

        uksort($array, function ($itemA, $itemB) {
            $indexA = array_search($itemA, array_keys($this->default), true);
            $indexB = array_search($itemB, array_keys($this->default), true);

            return $indexA <=> $indexB;
        });

        fputcsv($this->handle, $array);
    }
}
