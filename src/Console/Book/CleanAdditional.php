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
use function array_filter;
use function count;
use function is_array;
use function reset;
use function sprintf;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

class CleanAdditional extends EditBooksCommand
{
    /** @var int */
    private $booksChanged = 0;

    /** @var int */
    private $totalChanges = 0;

    protected function configure(): void
    {
        $this
            ->setName('book:data:clean')
            ->setDescription('Remove empty data from additional and other list');
        parent::configure();
    }

    /**
     * @phan-suppress PhanUnusedProtectedMethodParameter
     */
    protected function afterExecute(InputInterface $input, OutputInterface $output, SymfonyStyle $style): void
    {
        parent::afterExecute($input, $output, $style);
        $style->success(['All books have been updated', sprintf('%d changes across %d books', $this->totalChanges, $this->booksChanged)]);
    }

    /**
     * @phan-suppress PhanUnusedProtectedMethodParameter
     */
    protected function beforeExecute(InputInterface $input, OutputInterface $output, SymfonyStyle $style, int $bookCount): void
    {
        $this->booksChanged = 0;
        $this->totalChanges = 0;
        parent::beforeExecute($input, $output, $style, $bookCount);
    }

    /**
     * @phan-suppress PhanUnusedProtectedMethodParameter
     */
    protected function editBook(InputInterface $input, OutputInterface $output, SymfonyStyle $style, Book $book): bool
    {
        $bookChanges = $this->cleanAdditional($book)
            + $this->cleanArray($book, 'authors')
            + $this->cleanArray($book, 'illustrators')
            + $this->cleanArray($book, 'genres')
            + $this->cleanArray($book, 'keywords');

        if ($bookChanges > 0) {
            $this->objectManager->persist($book);
            $this->booksChanged++;
            $this->totalChanges += $bookChanges;

            return true;
        }

        return false;
    }

    private function cleanAdditional(Book $book): int
    {
        $additional = $book->getAdditional();
        $newAdditional = array_filter($additional, function (array $item) {
            if (0 === count($item)) {
                return false;
            }
            if (1 === count($item) && '' === reset($item)) {
                return false;
            }

            return true;
        });
        if (count($additional) > count($newAdditional)) {
            $book->setAdditional($newAdditional);

            return count($additional) - count($newAdditional);
        }

        return 0;
    }

    private function cleanArray(Book $book, string $arrayName): int
    {
        $metadata = $this->objectManager->getClassMetadata(Book::class);
        $data = $metadata->getFieldValue($book, $arrayName);

        if (!is_array($data)) {
            return 0;
        }

        if (0 === count($data)) {
            return 0;
        }

        if (1 === count($data) && '' === reset($data)) {
            $metadata->setFieldValue($book, $arrayName, []);

            return 1;
        }

        return 0;
    }
}
