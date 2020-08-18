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
use function array_combine;
use function array_keys;
use function array_map;
use function array_values;
use function assert;
use Exception;
use function is_string;
use function lcfirst;
use Luchaninov\CsvFileLoader\CsvFileLoader;
use function sprintf;
use function str_replace;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use function ucwords;

class ImportCsv extends Command
{
    /** @var BookCreator */
    protected $bookCreator;

    /**
     * ImportCsv constructor.
     */
    public function __construct(BookCreator $bookCreator)
    {
        $this->bookCreator = $bookCreator;
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->setName('book:import:csv')
            ->addArgument('path', InputArgument::REQUIRED, 'Path of the CSV file to import')
            ->addOption('snake-case', null, InputOption::VALUE_NONE, 'Indicate that column use snake_case and not camelCase')
            ->addOption('tolerant', null, InputOption::VALUE_NONE, 'Indicate that column use snake_case and not camelCase');
    }

    /**
     * @return int
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $style = new SymfonyStyle($input, $output);

        $path = $input->getArgument('path');
        assert(is_string($path));

        $loader = new CsvFileLoader($path);
        $tolerant = (bool) $input->getOption('tolerant');

        $style->title(sprintf('Importing file <info>%s</info>', $path));

        try {
            $count = $loader->countItems();
        } catch (Exception $exception) {
            $style->error(['Unable to read file', $exception->getMessage()]);

            return 1;
        }

        if (0 === $count) {
            $style->warning('The file seem to be empty');

            return 2;
        }

        $style->note(sprintf('%d book(s) to import', $count));

        $style->progressStart($loader->countItems());
        foreach ($loader->getItems() as $item) {
            if ($input->getOption('snake-case')) {
                $item = $this->switchCase($item);
            }
            $bookData = $this->bookCreator->bookDataFromArray($item, $tolerant);
            $this->bookCreator->createBook($bookData);
            $style->progressAdvance();
        }
        $style->progressFinish();

        $style->success('Import OK');

        return 0;
    }

    /**
     * @param array<string,string> $data
     *
     * @return array<string,string>
     */
    private function switchCase(array $data): array
    {
        $keys = array_keys($data);
        $keys = array_map(function (string $key) {
            $words = ucwords(str_replace('_', ' ', $key));

            return lcfirst(
                str_replace(' ', '', $words)
            );
        }, $keys);

        return array_combine($keys, array_values($data)) ?: $data;
    }
}
