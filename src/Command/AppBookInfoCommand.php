<?php
/**
 * @author  MacFJA
 * @license MIT
 */
namespace App\Command;

use App\Entity\Book;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\TableCell;
use Symfony\Component\Console\Helper\TableSeparator;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

class AppBookInfoCommand extends Command
{
    /** @var string */
    protected static $defaultName = 'app:book:info';
    /**
     * @var EntityManagerInterface
     */
    private $entityManager;

    public function __construct(EntityManagerInterface $entityManager, string $name = null)
    {
        parent::__construct($name);
        $this->entityManager = $entityManager;
    }

    /**
     * @inheritdoc
     * @return void
     */
    protected function configure()
    {
        $this
            ->setDescription('Display information about a book')
            ->addArgument('ISBN', InputArgument::REQUIRED, 'The ISBN of the book')
            ->addOption(
                'col',
                'c',
                InputOption::VALUE_IS_ARRAY|InputOption::VALUE_REQUIRED,
                'List of columns to display',
                []
            )
            ->addOption('cols', null, InputOption::VALUE_REQUIRED, 'Comma separate list of columns to display', '')
            ->setHelp('Display information about a Book from the database.')
        ;
    }

    /**
     * @param InputInterface  $input
     * @param OutputInterface $output
     * @return int
     * @throws \UnexpectedValueException
     * @throws \Symfony\Component\Console\Exception\InvalidArgumentException
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $io = new SymfonyStyle($input, $output);
        $isbn = $input->getArgument('ISBN');
        $cols = array_filter(array_merge(
            $input->getOption('col'),
            explode(',', $input->getOption('cols'))
        ));

        $io->section('Search for ISBN "'.$isbn.'" in database');
        $cleanIsbn = preg_replace('/[^0-9xX]/', '', $isbn);
        $io->writeln('Cleaning ISBN... From "'.$isbn.'" to "'.$cleanIsbn.'"', OutputInterface::VERBOSITY_VERBOSE);
        $books = $this->entityManager->getRepository(Book::class)->findBy(['isbn' => $cleanIsbn]);
        $io->writeln('Found '.count($books).' book(s)');

        if (count($books) === 0) {
            return 1;
        }

        $book = array_shift($books);

        $this->displayBook($book, $io, $cols);

        foreach ($books as $book) {
            if (!$io->confirm('Display the next book?')) {
                break;
            }
            $this->displayBook($book, $io, $cols);
        }

        return 0;
    }

    /**
     * @param Book         $book
     * @param SymfonyStyle $io
     * @param array        $cols
     * @return void
     */
    protected function displayBook(Book $book, SymfonyStyle $io, array $cols = [])
    {
        $data = $book->toArray([], [
            Book::ARRAY_INJECT_OTHERS => true,
            Book::ARRAY_FLATTEN_OPTION => true,
            Book::ARRAY_ADD_STATUS => true
        ]);

        $authors = implode(', ', (array) ($book->getAuthor()??'<fg=red>Unknown</>'));

        $header = sprintf(
            '<info><options=bold>%s</> by <fg=blue>%s</> (%s)</info>',
            $book->getTitle(),
            $authors,
            $book->getIsbn(true)
        );

        $rows = [
            [new TableCell($header, ['colspan' => 2])],
            new TableSeparator()
        ];
        foreach ($data as $header => $value) {
            if (count($cols) > 0 && !in_array($header, $cols)) {
                continue;
            }
            $formattedValue = wordwrap($value, 80, PHP_EOL);
            $rows[] = [
                new TableCell('<info>' . strtoupper($header) . '</info>'),
                new TableCell($formattedValue),
            ];
        }

        $io->table([], $rows);
    }
}
