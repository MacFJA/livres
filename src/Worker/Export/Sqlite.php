<?php
/**
 * @author  MacFJA
 * @license MIT
 */
namespace App\Worker\Export;

use Symfony\Component\Filesystem\Filesystem;

class Sqlite implements ExportInterface
{
    /** @var  string */
    protected $databasePath;

    /**
     * Sqlite constructor.
     *
     * @param string $databasePath
     */
    public function __construct(string $databasePath)
    {
        $this->databasePath = str_replace('sqlite://', '', $databasePath);
    }

    /**
     * {@inheritDoc}
     * @throws \Symfony\Component\Filesystem\Exception\FileNotFoundException
     * @throws \Symfony\Component\Filesystem\Exception\IOException
     */
    public function export(string $output)
    {
        $fileSystem = new Filesystem();
        $fileSystem->copy($this->databasePath, $output);
    }

    public function getName(): string
    {
        return 'Sqlite (Copy database)';
    }

    public function getFormatCode(): string
    {
        return 'sqlite';
    }
}
