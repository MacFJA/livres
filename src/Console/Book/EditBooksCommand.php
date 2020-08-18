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
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

abstract class EditBooksCommand extends OnBooksCommand
{
    /** @var EntityManagerInterface */
    protected $objectManager;

    public function __construct(BookRepository $bookRepository, EntityManagerInterface $objectManager)
    {
        $this->objectManager = $objectManager;
        parent::__construct($bookRepository);
    }

    protected function configure(): void
    {
        $this->addOption('dry-run', 'd', InputOption::VALUE_NONE, 'Don\'t save any changes in database');
        parent::configure();
    }

    protected function executeOnBook(InputInterface $input, OutputInterface $output, SymfonyStyle $style, Book $book): void
    {
        if ($output->isVerbose()) {
            $style->newLine();
            $this->renderBook($book, $output)->render();
        }
        if ($this->editBook($input, $output, $style, $book) && false === $input->getOption('dry-run')) {
            if ($output->isVerbose()) {
                $this->renderBook($book, $output)->render();
            }
            $this->objectManager->persist($book);
        }
    }

    abstract protected function editBook(InputInterface $input, OutputInterface $output, SymfonyStyle $style, Book $book): bool;

    /**
     * @phan-suppress PhanUnusedProtectedMethodParameter
     */
    protected function afterExecute(InputInterface $input, OutputInterface $output, SymfonyStyle $style): void
    {
        if (false === $input->getOption('dry-run')) {
            $this->objectManager->flush();
        }
    }
}
