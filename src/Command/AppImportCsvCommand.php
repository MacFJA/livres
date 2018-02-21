<?php
/**
 * @author  MacFJA
 * @license MIT
 */
namespace App\Command;

use App\Worker\Import\Csv;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

class AppImportCsvCommand extends Command
{
    /** @var string */
    protected static $defaultName = 'app:import:csv';
    /** @var  Csv */
    protected $importer;

    /**
     * AppImportCsvCommand constructor.
     *
     * @param Csv $importer
     */
    public function __construct(Csv $importer)
    {
        parent::__construct();
        $this->importer = $importer;
    }

    /**
     * @inheritdoc
     * @return void
     */
    protected function configure()
    {
        $this
            ->setDescription('Add a short description for your command')
            ->addArgument('file', InputArgument::REQUIRED, 'Argument description')
        ;
    }
    
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $io = new SymfonyStyle($input, $output);
        $file = $input->getArgument('file');

        $this->importer->importDb($file);

        $io->success('Import finish');
    }
}
