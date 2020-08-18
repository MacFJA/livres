<?php

declare(strict_types=1);

/*
 * Copyright MacFJA
 *
 * Permission is hereby granted, free of charge, to any person obtaining a copy of this software and associated
 * documentation files (the "Software"), to deal in the Software without restriction, including without limitation the
 * rights to use, copy, modify, merge, publish, distribute, sublicense, and/or sell copies of the Software, and to
 * permit persons to whom the Software is furnished to do so, subject to the following conditions:
 *
 * The above copyright notice and this permission notice shall be included in all copies or substantial portions of the
 * Software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR IMPLIED, INCLUDING BUT NOT LIMITED TO THE
 * WARRANTIES OF MERCHANTABILITY, FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE AUTHORS OR
 * COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR
 * OTHERWISE, ARISING FROM, OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE SOFTWARE.
 */

namespace App\Worker;

use App\Entity\Book;
use function array_filter;
use function array_key_exists;
use function array_shift;
use function assert;
use DateTime;
use DateTimeInterface;
use DateTimeZone;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\EntityManagerInterface;
use EasySlugger\Utf8Slugger;
use Exception;
use function explode;
use function implode;
use function is_array;
use function is_int;
use function is_scalar;
use function is_string;
use function json_decode;
use function json_encode;
use const JSON_THROW_ON_ERROR;
use JsonException;
use RuntimeException;
use function serialize;
use function strtotime;
use function unserialize;

/**
 * @SuppressWarnings(PHPMD.ExcessiveClassComplexity)
 */
class BookCreator
{
    private const METHOD_ALL = 7;

    private const METHOD_JSON = 1;

    private const METHOD_EXPLODE = 2;

    private const METHOD_SERIALIZE = 4;

    /** @var EntityManagerInterface */
    protected $objectManager;

    /**
     * BookCreator constructor.
     */
    public function __construct(EntityManagerInterface $objectManager)
    {
        $this->objectManager = $objectManager;
    }

    /**
     * @param array<string,mixed> $data
     *
     * @throws JsonException
     * @suppress PhanPartialTypeMismatchArgument
     */
    public function createBook(array $data, ?int $withId = null): Book
    {
        $book = null;
        if (is_int($withId)) {
            /** @var null|Book $book */
            $book = $this->objectManager->find(Book::class, $withId);
        }
        $book = $book ?? new Book();

        $data = $this->bookDataFromArray($data);

        $data = array_filter($data, function ($item) {
            return !empty($item);
        });

        $data += [
            'sortTitle' => Utf8Slugger::slugify(($data['series'] ?? '').' - '.($data['title'] ?? '')) ?? '',
            'addedAt' => new DateTime(),
        ];

        $metadata = $this->objectManager->getClassMetadata(Book::class);

        $additional = [];
        foreach ($data as $key => $value) {
            if (!$metadata->hasField($key)) {
                $additional[$key] = $value;

                continue;
            }
            $metadata->setFieldValue($book, $key, $value);
        }
        $book->setAdditional($additional);

        $this->objectManager->persist($book);
        $this->objectManager->flush();

        return $book;
    }

    /**
     * @param array<string,mixed> $data
     *
     * @throws JsonException
     *
     * @return array<mixed>
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     * @suppress PhanDeprecatedClassConstant
     */
    public function bookDataFromArray(array $data, bool $tolerant = false): array
    {
        $metadata = $this->objectManager->getClassMetadata(Book::class);
        foreach ($data as $key => &$value) {
            if (!$metadata->hasField($key) && !is_array($value)) {
                $value = $this->rawScalarToArray($value);

                continue;
            }
            $type = $metadata->getTypeOfField($key);

            switch ($type) {
                case Types::JSON_ARRAY:
                case Types::JSON:
                    $value = $this->rawScalarToArray($value, true === $tolerant ? self::METHOD_ALL : self::METHOD_JSON);

                    break;
                case Types::SIMPLE_ARRAY:
                    $value = $this->rawScalarToArray($value, true === $tolerant ? self::METHOD_ALL : self::METHOD_EXPLODE);

                    break;
                case Types::ARRAY:
                    $value = $this->rawScalarToArray($value, true === $tolerant ? self::METHOD_ALL : self::METHOD_SERIALIZE);

                    break;
                case Types::DATE_MUTABLE:
                case Types::DATE_IMMUTABLE:

                case Types::DATETIME_MUTABLE:
                case Types::DATETIME_IMMUTABLE:

                case Types::TIME_MUTABLE:
                case Types::TIME_IMMUTABLE:
                    if (is_string($value) && !(false === strtotime($value))) {
                        $value = new DateTime($value);

                        break;
                    }
                    if (is_array($value) && array_key_exists('date', $value) && array_key_exists('timezone', $value)) {
                        $value = new DateTime($value['date'], new DateTimeZone($value['timezone']));

                        break;
                    }
                    $value = null;
            }
        }

        return $data;
    }

    /**
     * @return array<null|bool|float|int|string>
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     * @suppress PhanDeprecatedClassConstant
     */
    public function bookToArray(Book $book): array
    {
        $metadata = $this->objectManager->getClassMetadata(Book::class);

        $result = [];

        foreach ($metadata->getFieldNames() as $fieldName) {
            $rawValue = $metadata->getFieldValue($book, $fieldName);
            if (null === $rawValue) {
                $result[$fieldName] = $rawValue;

                continue;
            }
            switch ($metadata->getTypeOfField($fieldName)) {
                case Types::SIMPLE_ARRAY:
                    $result[$fieldName] = implode(',', $rawValue);

                    break;
                case Types::ARRAY:
                    $result[$fieldName] = serialize($rawValue);

                    break;
                case Types::JSON_ARRAY:
                case Types::JSON:
                    $result[$fieldName] = json_encode($rawValue) ?: '""';

                    break;
                case Types::DATE_MUTABLE:
                case Types::DATE_IMMUTABLE:
                    if ($rawValue instanceof DateTimeInterface) {
                        $result[$fieldName] = $rawValue->format('Y-m-d');
                    }

                    break;
                case Types::DATETIME_MUTABLE:
                case Types::DATETIME_IMMUTABLE:
                if ($rawValue instanceof DateTimeInterface) {
                    $result[$fieldName] = $rawValue->format(DateTime::ATOM);
                }

                    break;
                case Types::TIME_MUTABLE:
                case Types::TIME_IMMUTABLE:
                if ($rawValue instanceof DateTimeInterface) {
                    $result[$fieldName] = $rawValue->format('H:i:s');
                }

                    break;
                case Types::INTEGER:
                case Types::FLOAT:
                case Types::BIGINT:
                case Types::STRING:
                case Types::TEXT:
                case Types::BLOB:
                case Types::BOOLEAN:
                case Types::BINARY:
                case Types::DECIMAL:
                case Types::GUID:
                case Types::SMALLINT:
                    assert(is_scalar($rawValue));
                    $result[$fieldName] = $rawValue;

                    break;
            }
        }

        return $result;
    }

    /**
     * @param mixed|string $value
     *
     * @return array<mixed>
     *
     * @suppress PhanUnusedVariableCaughtException
     */
    private function rawScalarToArray($value, int $withMethod = self::METHOD_ALL): array
    {
        if (is_array($value)) {
            return $value;
        }

        $stack = [self::METHOD_JSON, self::METHOD_SERIALIZE, self::METHOD_EXPLODE, self::METHOD_ALL];

        while ($withMethod > 0) {
            try {
                return $this->rawScalarToArrayOrFail($value, $withMethod);
            } catch (Exception $exception) {
                $withMethod &= ~array_shift($stack);
            }
        }

        return [$value];
    }

    /**
     * @param mixed|string $value
     *
     * @throws JsonException
     * @throws RuntimeException
     *
     * @return array<mixed>
     */
    private function rawScalarToArrayOrFail($value, int $withMethod = self::METHOD_ALL): array
    {
        if (self::METHOD_JSON === ($withMethod & self::METHOD_JSON)) {
            $result = json_decode($value, true, 10, JSON_THROW_ON_ERROR);
            if (!is_array($result)) {
                throw new JsonException('Unexpected parsed JSON ');
            }

            return $result;
        }
        if (self::METHOD_SERIALIZE === ($withMethod & self::METHOD_SERIALIZE)) {
            $data = @unserialize($value, ['allowed_classes' => false]);
            if (is_array($data)) {
                return $data;
            }

            if (self::METHOD_SERIALIZE === $withMethod) {
                throw new RuntimeException('Unable to unserialize the data');
            }
        }
        if (self::METHOD_EXPLODE === ($withMethod & self::METHOD_EXPLODE)) {
            return explode(',', $value);
        }

        throw new RuntimeException('Unable to transform');
    }
}
