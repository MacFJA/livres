<?php
/**
  * @author  MacFJA
  * @license MIT
  */
namespace App\Worker\Entity;

use App\Entity\Book;
use App\Entity\Movement;

class Person
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

    public function getAllPersons(): array
    {
        $books = $this->entityWorker->getAllValues(Book::class, 'owner');
        $movements = $this->entityWorker->getAllValues(Movement::class, 'person');
        $people = array_merge($books, $movements);
        $people = array_unique($people);
        natsort($people);

        return array_filter($people);
    }
}
