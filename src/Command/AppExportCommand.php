<?php
/**
 * @author  MacFJA
 * @license MIT
 */
namespace App\Command;

use App\Worker\Entity\BookInjectionListener;
use App\Worker\Export\ExportInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

class AppExportCommand extends Command
{
    /** @var string */
    protected static $defaultName = 'app:export';
    /**
     * @var \App\Worker\Export\ExportInterface[]
     */
    private $exporters;
    /**
     * @var BookInjectionListener
     */
    private $bookInjectionListener;

    /**
     * AppExportCommand constructor.
     *
     * @param BookInjectionListener                $bookInjectionListener
     * @param ExportInterface[]|\IteratorAggregate $exporters
     * @param null|string                          $name
     */
    public function __construct(BookInjectionListener $bookInjectionListener, $exporters, $name = null)
    {
        $this->exporters = ($exporters instanceof \IteratorAggregate)
            ? iterator_to_array($exporters->getIterator())
            : $exporters;
        $this->bookInjectionListener = $bookInjectionListener;
        parent::__construct($name);
    }

    /**
     * @inheritdoc
     * @return void
     */
    protected function configure()
    {
        $this
            ->setDescription('Export the database')
            ->addArgument('file', InputArgument::REQUIRED, 'The file where to export')
            ->addOption('format', 'f', InputOption::VALUE_REQUIRED, 'The format to use for the export', 'csv')
        ;

        $formatList = ' - '. implode(PHP_EOL.' - ', array_map(function (ExportInterface $export): string {
            return '<info>'.$export->getFormatCode().'</info> ('.$export->getName().')';
        }, $this->exporters)). PHP_EOL;

        $this->setHelp(sprintf(<<<END
Export the database to a file.

Available format are:
%s
END
        , $formatList));
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $io = new SymfonyStyle($input, $output);
        $file = $input->getArgument('file');

        $exporter = $this->getExporter($input->getOption('format'));

        $io->title('Exporting with "'.$exporter->getName().'"');

        $this->bookInjectionListener->setDisableCoverInjection(true);

        $exporter->export($file);

        $io->success('Export finish');
    }

    public function getExporter(string $format): ExportInterface
    {
        $validCode = [];
        foreach ($this->exporters as $exporter) {
            $validCode[] = $exporter->getFormatCode();
            if ($exporter->getFormatCode() == $format) {
                return $exporter;
            }
        }
        throw new \InvalidArgumentException(implode(', ', $validCode));
    }
}
