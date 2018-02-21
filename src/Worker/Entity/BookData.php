<?php
/**
  * @author  MacFJA
  * @license MIT
  */
namespace App\Worker\Entity;

use App\Entity\Book;

class BookData
{
    /** @var  Generic */
    protected $entityWorker;

    /**
     * Person constructor.
     *
     * @param Generic $entityWorker
     */
    public function __construct(Generic $entityWorker)
    {
        $this->entityWorker = $entityWorker;
    }

    protected function getField(string $field): array
    {
        $fieldValues = $this->entityWorker->getAllValues(Book::class, $field);
        $fieldValues = array_reduce(
            $fieldValues,
            /**
             * @param array        $carry
             * @param array|string $value
             */
            function (array $carry, $value): array {
                if (is_array($value)) {
                    return array_merge($carry, $value);
                }
                $carry[] = $value;
                return $carry;
            },
            []
        );
        $fieldValues = array_unique($fieldValues);
        natsort($fieldValues);

        return array_values(array_filter($fieldValues));
    }

    public function getOwners(): array
    {
        return $this->getField('owner');
    }
    public function getAuthors(): array
    {
        return $this->getField('author');
    }
    public function getSeries(): array
    {
        return $this->getField('serie');
    }
    public function getPublishers(): array
    {
        return $this->getField('publisher');
    }
    public function getIllustrators(): array
    {
        return $this->getField('illustrator');
    }
    public function getTranslators(): array
    {
        return $this->getField('translator');
    }
    public function getGenres(): array
    {
        return $this->getField('genre');
    }
    public function getEditions(): array
    {
        return $this->getField('edition');
    }
    public function getEditors(): array
    {
        return $this->getField('editor');
    }
    public function getFormats(): array
    {
        return $this->getField('format');
    }
    public function getStorages(): array
    {
        return $this->getField('storage');
    }
}
