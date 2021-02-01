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

namespace App\Model\Output;

use ApiPlatform\Core\Annotation\ApiProperty;
use App\Entity\Book;
use function array_filter;
use function array_keys;
use function array_map;
use function array_merge;
use function array_values;
use function json_encode;
use MacFJA\BookRetriever\SearchResultInterface;
use function md5;

class ProviderSearchResult
{
    /**
     * @ApiProperty(identifier=true,readable=false)
     *
     * @var string
     */
    public $resultId;

    /**
     * @ApiProperty(readable=true)
     *
     * @var array<ProviderSearchResultData>
     */
    public $data;

    /**
     * ProviderSearchResultItem constructor.
     *
     * @param ProviderSearchResultData[] $data
     */
    private function __construct(array $data)
    {
        $this->resultId = md5(json_encode($data) ?: '');
        $this->data = $data;
    }

    public static function create(SearchResultInterface $result): self
    {
        $data = [
            new ProviderSearchResultData('replace', Book::DATA_TYPE_TEXT, 'title', 'Title', $result->getTitle()),
            new ProviderSearchResultData('replace', Book::DATA_TYPE_BARCODE, 'isbn', 'Isbn', $result->getIsbn()),
            new ProviderSearchResultData('replace', Book::DATA_TYPE_DATE, 'publicationDate', 'Publication Date', $result->getPublicationDate()),
            new ProviderSearchResultData('replace', Book::DATA_TYPE_NUMBER, 'pages', 'Pages', $result->getPages()),
            new ProviderSearchResultData('concat', Book::DATA_TYPE_TEXT, 'series', 'Series', $result->getSeries()),
            new ProviderSearchResultData('push', Book::DATA_TYPE_ARRAY, 'keywords', 'Keywords', $result->getKeywords()),
            new ProviderSearchResultData('push', Book::DATA_TYPE_ARRAY, 'genres', 'Genres', $result->getGenres()),
            new ProviderSearchResultData('concat', Book::DATA_TYPE_TEXT, 'format', 'Format', $result->getFormat()),
            new ProviderSearchResultData('concat', Book::DATA_TYPE_TEXT, 'dimension', 'Dimension', $result->getDimension()),
            new ProviderSearchResultData('push', Book::DATA_TYPE_ARRAY, 'illustrator', 'Illustrator', $result->getIllustrators()),
            new ProviderSearchResultData('push', Book::DATA_TYPE_ARRAY, 'authors', 'Authors', $result->getAuthors()),
            new ProviderSearchResultData('push', Book::DATA_TYPE_ARRAY, 'translators', 'Translators', array_values($result->getTranslators())),
            new ProviderSearchResultData('replace', Book::DATA_TYPE_IMAGE, 'cover', 'Cover', $result->getCover()),
        ];
        $additional = $result->getAdditional();
        $additional = array_map(
            function ($key, $value) {
                return new ProviderSearchResultData('push', Book::DATA_TYPE_ARRAY, $key, $key, $value);
            },
            array_keys($additional), array_values($additional)
        );

        $bookData = array_merge($data, $additional);
        $bookData = array_filter($bookData, function (ProviderSearchResultData $data): bool {
            return !empty($data->getValue());
        });

        return new self(array_values($bookData));
    }
}
