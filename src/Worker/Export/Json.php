<?php
/**
 * @author  MacFJA
 * @license MIT
 */
namespace App\Worker\Export;

use App\Entity\Book;
use App\Worker\Entity\BookInjectionListener;
use Doctrine\ORM\EntityManagerInterface;

class Json implements ExportInterface
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

        $stringifyJson = json_encode($books);

        file_put_contents($output, $stringifyJson);
    }

    public function getName(): string
    {
        return 'JSON';
    }

    public function getFormatCode(): string
    {
        return 'json';
    }
}
