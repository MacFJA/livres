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

namespace App\ApiPlatform\DataTransformer;

use ApiPlatform\Core\DataTransformer\DataTransformerInterface;
use App\Doctrine\BookInjectionListener;
use App\Entity\Book;
use App\Model\Input\CreateBook as CreateBookModel;
use App\Model\Input\InputBookData;
use function array_key_exists;
use function array_reduce;
use DateTime;
use DateTimeInterface;
use DateTimeZone;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\EntityManagerInterface;
use EasySlugger\Utf8Slugger;
use Exception;
use function in_array;
use function is_array;
use function is_int;
use function is_string;
use function strtotime;

class CreateBook implements DataTransformerInterface
{
    private const DATE_TYPES = [
        Types::DATE_MUTABLE,
        Types::DATE_IMMUTABLE,
        Types::DATEINTERVAL,
        Types::DATETIME_MUTABLE,
        Types::DATETIME_IMMUTABLE,
        Types::DATETIMETZ_MUTABLE,
        Types::DATETIMETZ_IMMUTABLE,
    ];

    /** @var EntityManagerInterface */
    private $entityManager;

    /** @var BookInjectionListener */
    private $bookInjection;

    /**
     * CreateBook constructor.
     */
    public function __construct(EntityManagerInterface $entityManager, BookInjectionListener $bookInjection)
    {
        $this->entityManager = $entityManager;
        $this->bookInjection = $bookInjection;
    }

    /**
     * @param CreateBookModel|object $object
     * @param array<mixed>           $context
     *
     * @return Book|object
     *
     * @SuppressWarnings(PHPMD.ShortVariable)
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     * @suppress PhanUnusedPublicMethodParameter
     */
    public function transform($object, string $to, array $context = [])
    {
        if (!$object instanceof CreateBookModel) {
            return $object;
        }

        $book = new Book();
        if (($context['object_to_populate'] ?? null) instanceof Book) {
            $book = $context['object_to_populate'];
        }
        $metadata = $this->entityManager->getClassMetadata(Book::class);

        $data = array_reduce($object->fields, function (array $array, InputBookData $item): array {
            $array[$item->key] = $item->value;

            return $array;
        }, []);
        $data += [
            'sortTitle' => Utf8Slugger::slugify(($data['series'] ?? '').' - '.($data['title'] ?? '')) ?? '',
        ];
        if (!is_int($book->getBookId())) {
            $data += [
                'addedAt' => new DateTime(),
            ];
        }

        $additional = [];
        foreach ($data as $key => $value) {
            if (!$metadata->hasField($key)) {
                $additional[$key] = $value;

                continue;
            }
            if (in_array($metadata->getTypeOfField($key), self::DATE_TYPES, true)) {
                $value = $this->convertDate($value);
            }
            $metadata->setFieldValue($book, $key, $value);
        }
        $metadata->setFieldValue($book, 'additional', $additional);
        $this->bookInjection->handleCover($book, $metadata);

        return $book;
    }

    /**
     * @param array<mixed>|object $data
     * @param array<mixed>        $context
     * @SuppressWarnings(PHPMD.ShortVariable)
     */
    public function supportsTransformation($data, string $to, array $context = []): bool
    {
        return
            ('post_lite' === ($context['collection_operation_name'] ?? '') || 'put_lite' === ($context['item_operation_name'] ?? ''))
            && !$data instanceof Book
            && Book::class === $to
            && CreateBookModel::class === ($context['input']['class'] ?? null);
    }

    /**
     * @param array<string>|DateTimeInterface|mixed|string $value
     *
     * @throws Exception
     */
    private function convertDate($value): ?DateTimeInterface
    {
        if ($value instanceof DateTimeInterface) {
            return $value;
        }
        if (is_string($value) && !(false === strtotime($value))) {
            return new DateTime($value);
        }
        if (is_array($value) && array_key_exists('date', $value) && array_key_exists('timezone', $value)) {
            return new DateTime($value['date'], new DateTimeZone($value['timezone']));
        }

        return null;
    }
}
