<?php
/**
 * @author  MacFJA
 * @license MIT
 */
namespace App\Worker\Export;

use App\Entity\Book;
use Doctrine\ORM\EntityManagerInterface;
use League\Csv\Writer;

class Csv implements ExportInterface
{
    /** @var  EntityManagerInterface */
    protected $entityManager;

    /**
     * Csv constructor.
     *
     * @param EntityManagerInterface $entityManager
     */
    public function __construct(EntityManagerInterface $entityManager)
    {
        $this->entityManager = $entityManager;
    }

    /**
     * {@inheritDoc}
     * @throws \League\Csv\Exception
     */
    public function export(string $dataPath)
    {
        $headerMapping = \App\Worker\Import\Csv::getHeaderMapping();

        $csv = Writer::createFromString('');
        $csv->setDelimiter(',')->setEnclosure('"');

        $records = $this->entityManager->getRepository(Book::class)->findAll();

        /** @var Book $first */
        $first = reset($records);
        $header = array_keys($first->toArray(['cover', 'storage', 'addedAt'], [Book::ARRAY_KEEP_EMPTY => true]));
        sort($header);
        foreach ($header as &$value) {
            $value = $headerMapping[$value] ?? $value;
        }
        $csv->insertOne($header);

        /** @var Book $record */
        foreach ($records as $record) {
            $row = $record->toArray(
                ['cover', 'storage', 'addedAt'],
                [Book::ARRAY_KEEP_EMPTY => true, Book::ARRAY_FLATTEN_OPTION => true]
            );
            ksort($row);
            $csv->insertOne($row);
        }

        file_put_contents($dataPath, $csv->getContent());
    }

    public function getName(): string
    {
        return 'CSV';
    }

    public function getFormatCode(): string
    {
        return 'csv';
    }
}
