<?php
/**
  * @author  MacFJA
  * @license MIT
  */
namespace App\Entity;

use Doctrine\Common\Collections\Collection;

abstract class Base implements \JsonSerializable, \Serializable
{
    const ARRAY_FLATTEN_OPTION = 0;
    const ARRAY_DATE_FORMAT = 1;
    const ARRAY_KEEP_EMPTY = 2;

    /**
     * @return void
     */
    protected function convertStringToDate()
    {
        foreach ((new \ReflectionClass($this))->getProperties() as $property) {
            if (strpos($property->getDocComment(), 'DateTimeInterface') !== false) {
                $value = $this->{$property->name};
                if (!($value instanceof \DateTimeInterface) && !empty($value)) {
                    $this->{$property->name} = $this->checkDateFormatChain($value);
                }

                if (!($this->{$property->name} instanceof \DateTimeInterface)) {
                    $this->{$property->name} = null;
                }
            }
        }
    }

    /**
     * @param mixed $variable
     * @return false|\DateTime
     */
    private function checkDateFormatChain($variable)
    {
        if (($check = $this->checkDateFormatJson($variable)) !== false) {
            return $check;
        }
        if (($check = $this->checkDateFormatLocaleMonthYear($variable)) !== false) {
            return $check;
        }
        if (($check = $this->checkDateFormatTimestamp($variable)) !== false) {
            return $check;
        }
        return false;
    }

    /**
     * @param mixed $variable
     * @return false|\DateTime
     */
    private function checkDateFormatJson($variable)
    {
        if (!is_array($variable)) {
            return false;
        }
        if (array_key_exists('date', $variable) && array_key_exists('timezone', $variable)) {
            try {
                return \DateTime::createFromFormat('Y-m-d H:i:s.u', $variable['date']);
            } catch (\Exception $e) {
                return false;
            }
        }
        return false;
    }
    /**
     * @param mixed $variable
     * @return false|\DateTime
     */
    private function checkDateFormatTimestamp($variable)
    {
        if (!ctype_digit($variable)) {
            return false;
        }
        try {
            return \DateTime::createFromFormat('U', $variable);
        } catch (\Exception $e) {
            return false;
        }
    }
    /**
     * @param mixed $variable
     * @return false|\DateTime
     */
    private function checkDateFormatLocaleMonthYear($variable)
    {
        $formatter = new \IntlDateFormatter(
            'fr-FR',
            \IntlDateFormatter::MEDIUM,
            \IntlDateFormatter::NONE,
            new \DateTimeZone('Europe/Paris'),
            \IntlDateFormatter::GREGORIAN,
            'MM y'
        );
        if (!is_string($variable)) {
            return false;
        }
        try {
            return \DateTime::createFromFormat('U', (string) $formatter->parse($variable));
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * @param array $with
     * @return static
     */
    public static function createFromArray(array $with)
    {
        $entity = new static();
        list($valid,) = self::filterCreationArray($with);
        foreach ($valid as $property => $value) {
            $entity->{$property} = $value;
        }

        return $entity;
    }

    /**
     * @param array $with
     * @return array
     */
    protected static function filterCreationArray(array $with)
    {
        $valid = array_filter($with, function (string $key): bool {
            return property_exists(static::class, $key);
        }, ARRAY_FILTER_USE_KEY);

        $invalid = array_diff_key($with, $valid);

        return [$valid, $invalid];
    }

    public function toArray(array $excludes = [], array $options = []): array
    {
        $propertiesName = array_map(
            function (\ReflectionProperty $item): string {
                return $item->getName();
            },
            (new \ReflectionClass($this))->getProperties()
        );

        $array = [];
        foreach ($propertiesName as $name) {
            if (in_array($name, $excludes, true)) {
                continue;
            }
            $array[$name] = $this->{$name};
        }

        if ($options[self::ARRAY_FLATTEN_OPTION] ?? false) {
            $array = $this->flattenArray($array);
        }

        $array = array_map(
            /**
             * @param mixed $item
             * @return mixed
             */
            function ($item) use ($options) {
                return ($item instanceof \DateTimeInterface)
                    ? $item->format($options[self::ARRAY_DATE_FORMAT] ?? 'r')
                    : $item;
            },
            $array
        );

        if ($options[static::ARRAY_KEEP_EMPTY]??false) {
            return $array;
        }
        return array_filter($array);
    }

    protected function flattenArray(array $array): array
    {
        return array_map(
            /**
             * @param mixed $item
             * @return mixed
             */
            function ($item) {
                if (is_array($item)) {
                    return implode(', ', $item);
                } elseif ($item instanceof Collection) {
                    return implode(', ', $item->toArray());
                }
                return $item;
            },
            $array
        );
    }

    public static function isFieldValid(string $fieldName): bool
    {
        return property_exists(static::class, $fieldName);
    }

    /**
     * String representation of object
     *
     * @link  http://php.net/manual/en/serializable.serialize.php
     * @return string the string representation of the object or null
     * @since 5.1.0
     */
    public function serialize()
    {
        return serialize($this->toArray());
    }

    /**
     * Constructs the object
     *
     * @link  http://php.net/manual/en/serializable.unserialize.php
     * @param string $serialized <p>
     *                           The string representation of the object.
     *                           </p>
     * @return void
     * @since 5.1.0
     */
    public function unserialize($serialized)
    {
        $data = unserialize($serialized);
        $clone = static::createFromArray($data);

        $reflection = new \ReflectionClass($clone);
        foreach ($reflection->getProperties() as $property) {
            $this->{$property->getName()} = $clone->{$property->getName()};
        }
    }
}
