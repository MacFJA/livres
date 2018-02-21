<?php
/**
  * @author  MacFJA
  * @license MIT
  */
namespace App\Worker\Entity;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Mapping\ClassMetadata;

class Generic
{
    /** @var EntityManagerInterface */
    protected $entityManager;

    /**
     * Generic constructor.
     *
     * @param EntityManagerInterface $entityManager
     */
    public function __construct(EntityManagerInterface $entityManager)
    {
        $this->entityManager = $entityManager;
    }


    public function getAllValues(string $class, string $field): array
    {
        /** @var ClassMetadata $metaData */
        $metaData = $this->entityManager->getClassMetadata($class);

        if (!in_array($field, $metaData->fieldNames, true)) {
            throw new \InvalidArgumentException(vsprintf('Field "%s" does not exists in %s', [$field, $class]));
        }

        return array_column(
            $this->entityManager->createQuery(
                vsprintf('SELECT DISTINCT e.%s FROM %s e', [$field, $class])
            )->getResult(),
            $field
        );
    }
}
