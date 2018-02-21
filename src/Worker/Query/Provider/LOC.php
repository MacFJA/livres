<?php
/**
  * @author  MacFJA
  * @license MIT
  */
namespace App\Worker\Query\Provider;

use App\Worker\Query\QueryResult;
use GuzzleHttp\Client;

class LOC extends Base
{
    use SRUParserTrait;

    const API_PATTERN = 'http://lx2.loc.gov:210/lcdb' .
        '?version=1.1' .
        '&operation=searchRetrieve' .
        '&maximumRecords=5' .
        '&recordSchema=mods' .
        '&query=%s';

    protected function getSearchableField() : array
    {
        return ['isbn', 'ean', 'title', 'publisher', 'language', 'upc', 'lccn'];
    }

    protected function getParams(array $terms) : string
    {
        $mapping = [
            'isbn' => 'dc.identifier',
            'ean' => 'dc.identifier',
            'upc' => 'dc.identifier',
            'lccn' => 'dc.identifier',
            'title' => 'dc.title',
            'publisher' => 'dc.publisher',
            'language' => 'dc.language'
        ];

        $query = [];
        foreach ($terms as $field => $value) {
            if (!array_key_exists($field, $mapping)) {
                continue;
            }
            $query[] = $mapping[$field].'='.urlencode($value);
        }

        return implode('%20and%20', $query);
    }

    protected function doCompositeSearch(array $terms) : array
    {
        $client = new Client();
        $response = $client->get(sprintf(static::API_PATTERN, $this->getParams($terms)));

        if ($response->getStatusCode() != 200) {
            return [];
        }
        $source = $response->getBody()->getContents();

        $xml = simplexml_load_string($source);
        $nodes = $xml->xpath('//*[local-name()="recordData"]/*[local-name()="mods"]');
        $final = '<zs:doc xmlns:zs="http://www.loc.gov/zing/srw/">';
        foreach ($nodes as $node) {
            $final.= $node->asXML();
        }
        $final .= '</zs:doc>';

        return $this->parseSRU($this, $final, $terms);
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
        return 'loc';
    }

    public static function getLabel(): string
    {
        return 'The Library of Congress';
    }
}
