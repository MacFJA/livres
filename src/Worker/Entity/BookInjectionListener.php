<?php
/**
  * @author  MacFJA
  * @license MIT
  */
namespace App\Worker\Entity;

use App\Entity\Book;
use Doctrine\ORM\Event\LifecycleEventArgs;
use Doctrine\ORM\Mapping\ClassMetadata;
use GuzzleHttp\Client;
use Symfony\Component\Finder\Finder;

class BookInjectionListener
{
    /** @var string */
    protected $coverDir;
    /** @var bool */
    protected $disableCoverInjection = false;

    /**
     * @param boolean $disableCoverInjection
     * @return void
     */
    public function setDisableCoverInjection($disableCoverInjection)
    {
        $this->disableCoverInjection = $disableCoverInjection;
    }

    /**
     * BookInjectionListener constructor.
     *
     * @param string $coverDir
     */
    public function __construct(string $coverDir)
    {
        $this->coverDir = $coverDir;
    }

    /**
     * @param LifecycleEventArgs $lifecycleEvent
     * @return void
     */
    public function postLoad(LifecycleEventArgs $lifecycleEvent)
    {
        $entity = $lifecycleEvent->getEntity();
        if (!($entity instanceof Book)) {
            return;
        }

        $this->handleCover($entity, $lifecycleEvent->getEntityManager()->getClassMetadata(Book::class));
    }

    private function find(string $filename): bool
    {
        $finder = (new Finder())
            ->name($filename)
            ->in($this->coverDir)
            ->files();

        if (count($finder) == 0) {
            return false;
        } else {
            return true;
        }
    }

    /**
     * @param Book          $book
     * @param ClassMetadata $metadata
     * @return void
     */
    protected function handleCover(Book $book, ClassMetadata $metadata)
    {
        if ($this->disableCoverInjection) {
            return;
        }
        $cover = $book->getCover();

        if (empty($cover)) {
            return;
        }

        if (strpos($cover, 'http') !== 0) {
            return;
        }

        $filename =  $book->getId().'.'.(pathinfo(parse_url($cover, PHP_URL_PATH), PATHINFO_EXTENSION)?:'unk');
        if (!$this->find($filename)) {
            $client = new Client();
            $client->get($cover, ['sink' => $this->coverDir.DIRECTORY_SEPARATOR.$filename]);
        }

        $metadata->setFieldValue($book, 'cover', $filename);
    }
}
