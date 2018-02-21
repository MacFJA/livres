<?php
/**
 * @author  MacFJA
 * @license MIT
 */
namespace App\Worker\Export;

use App\Entity\Book;
use Doctrine\ORM\EntityManagerInterface;
use League\Csv\Writer;

class All implements ExportInterface
{
    /**
     * @var ExportInterface[]
     */
    private $exporters;

    /**
     * Csv constructor.
     *
     * @param EntityManagerInterface $entityManager
     * @param                        $exporters
     */
    public function __construct($exporters)
    {
        $this->exporters = $exporters;
    }

    /**
     * {@inheritDoc}
     */
    public function export(string $dataPath)
    {
        foreach ($this->exporters as $exporter) {
            if ($exporter instanceof All) {
                continue;
            }
            $exporter->export($dataPath.DIRECTORY_SEPARATOR.'export.'.$exporter->getFormatCode());
        }
    }

    public function getName(): string
    {
        return 'All exporters';
    }

    public function getFormatCode(): string
    {
        return 'all';
    }
}
