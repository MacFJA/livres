<?php
/**
  * @author  MacFJA
  * @license MIT
  */
namespace App\Worker\Query\Provider;

use App\Worker\Query\QueryResult;
use GuzzleHttp\Client;

class WorldCatIsbn extends BaseIsbn
{
    const API_URL = 'http://xisbn.worldcat.org/webservices/xid/isbn/%s?method=getMetadata&fl=*&format=json&count=1';
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

        $bookData = $json['list'];

        $result = [];

        foreach ($bookData as $book) {
            $result[] = QueryResult::createSimple($this, $field, $value, $book, [
                'worldcat_link' => isset($book['url'])?implode(', ', $book['url']):null,
                'publisher' => $book['publisher']??null,
                'lccn_id' => $book['lccn']??null,
                'author' => array_key_exists('author', $book)?[$book['author']]:null,
                'title' => $book['title']??null,
                'isbn' => isset($book['isbn'])?reset($book['isbn']):null,
                'oclc_id' => $book['oclcnum']??null,
                'publicationDate' => \DateTimeImmutable::createFromFormat('Y', $book['year']),
                'edition' => $book['edition']??null,
                'language' => $book['lang']??null
            ]);
        }

        return $result;
    }

    public function getCode(): string
    {
        return 'worldcat-isbn';
    }

    public static function getLabel(): string
    {
        return 'WorldCat ISBN';
    }
}
