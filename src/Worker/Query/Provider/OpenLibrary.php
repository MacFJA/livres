<?php
/**
 * @author  MacFJA
 * @license MIT
 */
namespace App\Worker\Query\Provider;

use App\Worker\Query\QueryResult;
use App\Worker\Query\Transformer\PathTransformerGroup;
use GuzzleHttp\Client;
use JsonPath\JsonObject;
use App\Worker\Query\Transformer\JsonPathTransformer as JPT;

class OpenLibrary extends BaseAggregate
{
    const API_URL_PATTERN = 'https://openlibrary.org/api/books?bibkeys=%s:%s&format=json&jscmd=data';

    public function search(string $field, string $value): array
    {
        $client = new Client();

        $searchType = $this->getSearchType($field);

        $response = $client->get(sprintf(self::API_URL_PATTERN, $searchType, $value));

        $json = \GuzzleHttp\json_decode($response->getBody()->getContents(), true);

        if (count($json) == 0) {
            return [];
        }

        $results = [];

        $groupPath = new PathTransformerGroup();
        $groupPath
            ->addTransformer(new JPT('$.publishers..name', '$.publisher', false))
            ->addTransformer(new JPT('$.identifiers.isbn_13.*', '$.isbn', false))
            ->addTransformer(new JPT('$.identifiers.google.*', '$.google_id', false))
            ->addTransformer(new JPT('$.identifiers.lccn.*', '$.lccn_id', false))
            ->addTransformer(new JPT('$.identifiers.amazon.*', '$.amazon_id', false))
            ->addTransformer(new JPT('$.identifiers.oclc.*', '$.oclc_id', false))
            ->addTransformer(new JPT('$.identifiers.librarything.*', '$.librarything_id', false))
            ->addTransformer(new JPT('$.identifiers.project_gutenberg.*', '$.project_gutenberg_id', false))
            ->addTransformer(new JPT('$.identifiers.goodreads.*', '$.goodreads_id', false))
            ->addTransformer(new JPT('$.identifiers.openlibrary.*', '$.openlibrary_id', false))
            ->addTransformer(new JPT('$.links', '$.links', true))
            ->addTransformer(new JPT('$.weight', '$.weight', false))
            ->addTransformer(new JPT('$.title', '$.title', false))
            ->addTransformer(new JPT('$.url', '$.openlibrary_link', false))
            ->addTransformer(new JPT('$.number_of_pages', '$.pages', false))
            ->addTransformer(new JPT('$.cover.large', '$.cover', false))
            ->addTransformer(new JPT('$.subjects.*.name', '$.genre', true))
            ->addTransformer(new JPT('$.authors.*.name', '$.author', true));

        foreach ($json as $searchResult) {
            $responseObject = new JsonObject($searchResult);
            $result = $groupPath->apply($responseObject);

            $publishingDate = JPT::getValue($responseObject, '$.publish_date', false);
            if ($publishingDate != null) {
                $publishingDate = \DateTime::createFromFormat('Y', $publishingDate);
            }
            $result['publicationDate'] = $publishingDate;

            $results[] = QueryResult::createSimple($this, $field, $value, $json, $result);
        }
        return $results;
    }

    /**
     * @param string $field
     * @return string
     * @throws \InvalidArgumentException
     */
    private function getSearchType(string $field) : string
    {
        switch (strtolower($field)) {
            case 'isbn':
            case 'ean':
                $searchType = 'ISBN';
                break;
            case static::FIELD_INTERNAL:
            case 'olid':
                $searchType = 'OLID';
                break;
            case 'oclc':
                $searchType = 'OCLC';
                break;
            case 'lccn':
                $searchType = 'LCCN';
                break;
            default:
                throw new \InvalidArgumentException('The field "'.$field.'" is not handled by this provider');
        }

        return $searchType;
    }

    public function getCode(): string
    {
        return 'open-library';
    }

    public static function getLabel(): string
    {
        return 'Open Library Books';
    }

    protected function getSearchableField() : array
    {
        return ['isbn','ean', static::FIELD_INTERNAL, 'olid', 'oclc', 'lccn'];
    }
}
