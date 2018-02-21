<?php
/**
  * @author  MacFJA
  * @license MIT
  */
namespace App\Worker\Entity;

use Doctrine\DBAL\Types\Type;
use Doctrine\ORM\EntityManagerInterface;

class PredefinedType
{
    /** @var EntityManagerInterface */
    protected $entityManager;

    /**
     * PredefinedType constructor.
     *
     * @param EntityManagerInterface $entityManager
     */
    public function __construct(EntityManagerInterface $entityManager)
    {
        $this->entityManager = $entityManager;
    }

    /**
     * @param $class
     * @param $field
     * @return string
     * @throws \InvalidArgumentException
     */
    public function getType($class, $field): string
    {
        $classMetaData = $this->entityManager->getClassMetadata($class);
        
        $type = $classMetaData->getTypeOfField($field);
        
        if ($type === null) {
            throw new \InvalidArgumentException('$field not found in class ('.$class.'->'.$field.')');
        }
        
        return ($type instanceof Type) ? $type->getName() : $type;
    }

    /**
     * @param $class
     * @return string[]
     */
    public function getTypes($class): array
    {
        $classMetaData = $this->entityManager->getClassMetadata($class);

        $result = [];
        foreach ($classMetaData->getFieldNames() as $fieldName) {
            try {
                $result[$fieldName] = $this->getType($class, $fieldName);
            } catch (\Exception $exception) {
                // Do nothing
                // Ignore invalid type
            }
        }

        return $result;
    }
}
