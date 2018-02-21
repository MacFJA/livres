<?php
/**
 * @author  MacFJA
 * @license MIT
 */
namespace App\Worker\Export;

use App\Entity\Book;
use App\Worker\Entity\BookInjectionListener;
use Doctrine\ORM\EntityManagerInterface;
use Ivory\Serializer\Format;
use Ivory\Serializer\Serializer;

class Yaml implements ExportInterface
{

    /** @var  EntityManagerInterface */
    protected $entityManager;
    /**
     * @var BookInjectionListener
     */
    private $bookInjectionListener;

    /**
     * Json constructor.
     *
     * @param EntityManagerInterface $entityManager
     * @param BookInjectionListener  $bookInjectionListener
     */
    public function __construct(EntityManagerInterface $entityManager, BookInjectionListener $bookInjectionListener)
    {
        $this->entityManager = $entityManager;
        $this->bookInjectionListener = $bookInjectionListener;
    }

    /**
     * {@inheritDoc}
     */
    public function export(string $output)
    {
        $this->bookInjectionListener->setDisableCoverInjection(true);
        $books = $this->entityManager->getRepository(Book::class)->findAll();

        $serialize = new Serializer();

        file_put_contents($output, $serialize->serialize($books, Format::YAML));
    }

    public function getName(): string
    {
        return 'YAML';
    }

    public function getFormatCode(): string
    {
        return 'yml';
    }
}
