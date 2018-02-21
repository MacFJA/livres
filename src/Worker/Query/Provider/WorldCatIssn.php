<?php
/**
  * @author  MacFJA
  * @license MIT
  */
namespace App\Worker\Query\Provider;

use App\Worker\Query\QueryResult;
use GuzzleHttp\Client;

class WorldCatIssn extends BaseIsbn
{
    const API_URL = 'http://xissn.worldcat.org/webservices/xid/issn/%s?method=getMetadata&fl=*&format=json&count=1';
    /**
     * @param string $field
     * @param string $value
     * @return QueryResult[]
     */
    public function search(string $field, string $value): array
    {
        $client = new Client();
        $response = $client->get(sprintf(static::API_URL, urlencode($value)));
        
        $json = \GuzzleHttp\json_decode($response->getBody()->getContents(), true);

        if ($json['stat'] != 'ok') {
            return [];
        }

        $bookData = $json['group']['list'];

        $result = [];

        foreach ($bookData as $book) {
            $result[] = QueryResult::createSimple($this, $field, $value, $book, [
                'publisher' => $book['publisher']??null,
                'title' => $book['tile']??null,
                'issn' => $book['issn']??null,
                'oclc_id' => $book['oclcnum']??null,
                'coverage' => $book['rawcoverage']??null,
                'worldcat_rss' => $book['rssurl']??null
            ]);
        }

        return $result;
    }

    public function getCode(): string
    {
        return 'worldcat-issn';
    }

    public static function getLabel(): string
    {
        return 'WorldCat ISSN';
    }
}
