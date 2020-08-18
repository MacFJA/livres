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

namespace App\Controller;

use App\Entity\Book;
use function array_filter;
use function array_keys;
use function array_map;
use function array_merge;
use function array_values;
use function count;
use Doctrine\Common\Annotations\Annotation\IgnoreAnnotation;
use function is_array;
use MacFJA\BookRetriever\ProviderConfigurationInterface;
use MacFJA\BookRetriever\ProviderConfigurator;
use MacFJA\BookRetriever\ProviderInterface;
use MacFJA\BookRetriever\SearchResultInterface;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;

/**
 * Class DebugController.
 *
 * @IgnoreAnnotation("suppress")
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class ProviderController extends AbstractController
{
    /**
     * @IsGranted("ROLE_CAN_ADD")
     *
     * @param iterable<ProviderInterface> $providers
     * @Route("/provider/search/{code<[\w-]+>}/{isbn<[\d-]+>}", name="search_isbn")
     */
    public function search(iterable $providers, ProviderConfigurationInterface $configuration, string $code, string $isbn): JsonResponse
    {
        $configurator = new ProviderConfigurator($configuration, null, null, null);
        /** @var ProviderInterface $provider */
        foreach ($providers as $provider) {
            if ($provider->getCode() === $code) {
                $configurator->configure($provider);
                $results = $provider->searchIsbn($isbn);
                $results = array_map(function (SearchResultInterface $searchResult) {
                    return $this->transformResult($searchResult);
                }, $results);

                return new JsonResponse($results, count($results) > 0 ? 200 : 404);
            }
        }

        return new JsonResponse([], 400);
    }

    /**
     * @return array<int, array<mixed>>
     * @phan-return array<int, array{fusion:string,type:string,key:string,label:string,value:mixed}>
     *
     * @psalm-return list<array{fusion: string, type: string, key: string, label: string, value: \DateTimeInterface|array|int|null|string}>
     */
    private function transformResult(SearchResultInterface $searchResult): array
    {
        $additional = $searchResult->getAdditional();
        $additional = array_map(
            function ($key, $value) {
                return ['fusion' => 'push', 'type' => Book::DATA_TYPE_ARRAY, 'key' => $key, 'label' => $key, 'value' => $value];
            },
            array_keys($additional), array_values($additional)
        );

        $labelValue = array_merge(
            [
                ['fusion' => 'replace', 'type' => Book::DATA_TYPE_TEXT, 'key' => 'title', 'label' => 'Title', 'value' => $searchResult->getTitle()],
                ['fusion' => 'replace', 'type' => Book::DATA_TYPE_BARCODE, 'key' => 'isbn', 'label' => 'Isbn', 'value' => $searchResult->getIsbn()],
                ['fusion' => 'replace', 'type' => Book::DATA_TYPE_DATE, 'key' => 'publicationDate', 'label' => 'Publication Date', 'value' => $searchResult->getPublicationDate()],
                ['fusion' => 'replace', 'type' => Book::DATA_TYPE_NUMBER, 'key' => 'pages', 'label' => 'Pages', 'value' => $searchResult->getPages()],
                ['fusion' => 'concat', 'type' => Book::DATA_TYPE_TEXT, 'key' => 'series', 'label' => 'Series', 'value' => $searchResult->getSeries()],
                ['fusion' => 'push', 'type' => Book::DATA_TYPE_ARRAY, 'key' => 'keywords', 'label' => 'Keywords', 'value' => $searchResult->getKeywords()],
                ['fusion' => 'push', 'type' => Book::DATA_TYPE_ARRAY, 'key' => 'genres', 'label' => 'Genres', 'value' => $searchResult->getGenres()],
                ['fusion' => 'concat', 'type' => Book::DATA_TYPE_TEXT, 'key' => 'format', 'label' => 'Format', 'value' => $searchResult->getFormat()],
                ['fusion' => 'concat', 'type' => Book::DATA_TYPE_TEXT, 'key' => 'dimension', 'label' => 'Dimension', 'value' => $searchResult->getDimension()],
                ['fusion' => 'push', 'type' => Book::DATA_TYPE_ARRAY, 'key' => 'illustrator', 'label' => 'Illustrator', 'value' => $searchResult->getIllustrators()],
                ['fusion' => 'push', 'type' => Book::DATA_TYPE_ARRAY, 'key' => 'authors', 'label' => 'Authors', 'value' => $searchResult->getAuthors()],
                ['fusion' => 'push', 'type' => Book::DATA_TYPE_ARRAY, 'key' => 'translators', 'label' => 'Translators', 'value' => array_values($searchResult->getTranslators())],
                ['fusion' => 'replace', 'type' => Book::DATA_TYPE_IMAGE, 'key' => 'cover', 'label' => 'Cover', 'value' => $searchResult->getCover()],
            ],
            $additional
        );

        return array_values(array_filter($labelValue, function (array $item) {
            $value = $item['value'];

            return !(null === $value) && (!is_array($value) || count($value) > 0);
        }));
    }
}
