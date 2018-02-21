<?php
/**
  * @author  MacFJA
  * @license MIT
  */
namespace App\Worker\Query\Provider;

use App\Worker\Query\QueryResult;
use GuzzleHttp\Client;

class COPAC extends Base
{
    use SRUParserTrait;

    const BASE_API_URL = 'http://copac.jisc.ac.uk/search?format=XML+-+MODS&%s';

    protected function getSearchableField() : array
    {
        return [
            'isbn','author','title','ean','issn','publisher'
        ];
    }

    protected function doCompositeSearch(array $terms) : array
    {
        $client = new Client();
        $response = $client->get(sprintf(static::BASE_API_URL, $this->getParams($terms)));

        if ($response->getStatusCode() != 200) {
            return [];
        }
        $source = $response->getBody()->getContents();

        return $this->parseSRU($this, $source, $terms);
    }

    protected function getParams(array $terms) : string
    {
        $mapping = [
            'isbn' => 'isn',
            'author' => 'au',
            'title' => 'ti',
            'ean' => 'isn',
            'issn' => 'isn',
            'publisher' => 'pub'
        ];

        $query = [];
        foreach ($terms as $field => $value) {
            if (!array_key_exists($field, $mapping)) {
                continue;
            }
            $query[$mapping[$field]] = $value;
        }

        return http_build_query($query);
    }

    /**
     * @param string $field
     * @param string $value
     * @return QueryResult[]
     */
    public function search(string $field, string $value): array
    {
        return $this->doCompositeSearch([$field => $value]);
    }

    public function getCode(): string
    {
        return 'copac';
    }

    public static function getLabel(): string
    {
        return 'COCAP*';
    }
}
