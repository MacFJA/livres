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

use App\Worker\BookCreator;
use function assert;
use function is_string;
use pcrov\JsonReader\JsonReader;
use function sprintf;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\ConsoleOutputInterface;
use Symfony\Component\Console\Output\ConsoleSectionOutput;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

class ImportJson extends Command
{
    /** @var BookCreator */
    protected $bookCreator;

    /**
     * ImportJson constructor.
     */
    public function __construct(BookCreator $bookCreator)
    {
        $this->bookCreator = $bookCreator;
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->setName('book:import:json')
            ->addArgument('path', InputArgument::REQUIRED, 'Path of the Json file to import');
    }

    /**
     * @return int
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $style = new SymfonyStyle($input, $output);

        $path = $input->getArgument('path');
        assert(is_string($path));

        $reader = new JsonReader();
        $reader->open($path);

        $style->title(sprintf('Importing file <info>%s</info>', $path));

        $reader->read(); // Begin array
        $reader->read(); // First element, or end of array @phan-suppress-current-line PhanPluginDuplicateAdjacentStatement
        $count = 0;

        $sectionOutput = null;
        if ($output instanceof ConsoleOutputInterface) {
            $sectionOutput = $output->section();
        }

        while (JsonReader::OBJECT === $reader->type()) {
            $count++;
            $this->showProgress($sectionOutput ?? $output, sprintf('Reading item %d...', $count));
            $bookData = $reader->value();
            $this->bookCreator->createBook($bookData);

            $reader->next();
        }

        $reader->close();

        $style->success('Import OK');

        return 0;
    }

    private function showProgress(OutputInterface $output, string $message): void
    {
        if ($output instanceof ConsoleSectionOutput) {
            $output->overwrite($message);

            return;
        }

        $output->writeln($message);
    }
}
