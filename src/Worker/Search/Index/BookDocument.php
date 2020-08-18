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

namespace App\Worker\Search\Index;

use App\Entity\Book;
use DateTimeInterface;
use Ehann\RediSearch\Document\AbstractDocumentFactory;
use Ehann\RediSearch\Document\DocumentInterface;
use Ehann\RediSearch\Fields\NumericField;
use Ehann\RediSearch\Fields\TagField;
use Ehann\RediSearch\Fields\TextField;
use Ehann\RediSearch\Index;
use function implode;
use function json_encode;

/**
 * Class BookDocument.
 *
 * @property NumericField $bookId
 * @property TextField $isbn
 * @property TextField $title
 * @property TagField $author
 * @property NumericField $pages
 * @property TextField $series
 * @property TextField $sortTitle
 * @property TextField $owner
 * @property TagField $illustrator
 * @property TagField $genre
 * @property TextField $publicationDate
 * @property TextField $format
 * @property TextField $dimension
 * @property TagField $keyword
 * @property TextField $additional
 */
class BookDocument extends \Ehann\RediSearch\Document\Document
{
    public static function createIndexDefinition(Index $index): void
    {
        $index
            ->addNumericField('bookId', false, true)
            ->addTextField('isbn')
            ->addTextField('title', 1.0, true)
            ->addTagField('author', true, false, '|')
            ->addNumericField('pages', true)
            ->addTextField('series', 1.0, true)
            ->addTextField('sortTitle', 0.5, true)
            ->addTextField('owner')
            ->addTagField('illustrator', true, false, '|')
            ->addTagField('genre', true, false, '|')
            ->addTextField('publicationDate', 0.5)
            ->addTextField('format')
            ->addTextField('dimension')
            ->addTagField('keyword', true, false, '|')
            ->addTextField('additional')
            ->create();
    }

    /**
     * @throws \Ehann\RediSearch\Exceptions\FieldNotInSchemaException
     *
     * @return BookDocument|DocumentInterface
     */
    public static function createFromBook(Book $book): DocumentInterface
    {
        $publicationDate = $book->getPublicationDate();
        $date = ($publicationDate instanceof DateTimeInterface) ? $publicationDate->format('Y-m-d') : '';

        return AbstractDocumentFactory::makeFromArray(
            [
                new NumericField('bookId', $book->getBookId()),
                new TextField('isbn', $book->getIsbn()),
                new TextField('title', $book->getTitle()),
                new TagField('author', implode('|', $book->getAuthors())),
                new NumericField('pages', $book->getPages() ?? -1),
                new TextField('series', $book->getSeries() ?? ''),
                new TextField('sortTitle', $book->getSortTitle()),
                new TextField('owner', $book->getOwner()),
                new TagField('illustrator', implode('|', $book->getIllustrators())),
                new TagField('genre', implode('|', $book->getGenres())),
                new TextField('publicationDate', $date),
                new TextField('format', $book->getFormat() ?? ''),
                new TextField('dimension', $book->getDimension() ?? ''),
                new TagField('keyword', implode('|', $book->getKeywords())),
                new TextField('additional', json_encode($book->getAdditional())),
            ],
            [
                'bookId' => new NumericField('bookId'),
                'isbn' => new TextField('isbn'),
                'title' => new TextField('title'),
                'author' => new TagField('author'),
                'pages' => new NumericField('pages'),
                'series' => new TextField('series'),
                'sortTitle' => new TextField('sortTitle'),
                'owner' => new TextField('owner'),
                'illustrator' => new TagField('illustrator'),
                'genre' => new TagField('genre'),
                'publicationDate' => new TextField('publicationDate'),
                'format' => new TextField('format'),
                'dimension' => new TextField('dimension'),
                'keyword' => new TagField('keyword'),
                'additional' => new TextField('additional'),
            ],
            self::getBookIndexId($book)
        );
    }

    public static function getBookIndexId(Book $book): string
    {
        return 'book-'.$book->getBookId();
    }
}
