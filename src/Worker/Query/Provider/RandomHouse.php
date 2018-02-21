<?php
/**
  * @author  MacFJA
  * @license MIT
  */
namespace App\Worker\Query\Provider;

use App\Entity\Book;
use App\Worker\Query\QueryResult;
use App\Worker\Query\Transformer\PathTransformerGroup;
use App\Worker\Query\Transformer\JsonPathTransformer as JPT;
use GuzzleHttp\Client;
use JsonPath\JsonObject;

class RandomHouse extends BaseIsbn
{
    const API_PATTERN = 'https://reststop.randomhouse.com/resources/titles/%s/';
    /**
     * @param string $field
     * @param string $value
     * @return QueryResult[]
     */
    public function search(string $field, string $value): array
    {
        $value = Book::convertISBNToEAN($value);
        $client = new Client(['headers' => ['Accept' => 'application/json']]);
        $response = $client->get(sprintf(static::API_PATTERN, $value));

        if ($response->getStatusCode() != 200) {
            return [];
        }

        $json = \GuzzleHttp\json_decode($response->getBody()->getContents(), true);
        $jsonObject = new JsonObject($json);
        $group = new PathTransformerGroup();
        $group
            ->addTransformer(new JPT('$.authorweb', '$.author', true))
            ->addTransformer(new JPT('$.formatname', '$.format'))
            ->addTransformer(new JPT('$.isbn', '$.isbn'))
            ->addTransformer(new JPT('$.keyword', '$.keywords'))
            ->addTransformer(new JPT('$.pages', '$.pages'))
            ->addTransformer(new JPT('$.titleweb', '$.title'))
            ->addTransformer(new JPT('$.links', '$.randomhouse_link'))
            ->addTransformer(new JPT('$.imprint', '$.publisher'))
            ->addTransformer(new JPT('$.authorbio', '$.author_bio'))
            ->addTransformer(new JPT('$.workid', '$.randomhouse_id'))
        ;

        $result = $group->apply($jsonObject);
        $date = JPT::getValue($jsonObject, '$.onsaledate');
        if ($date != null) {
            $date = \DateTime::createFromFormat('m/d/Y', $date);
        }
        $result['publicationDate'] = $date;

        $genre = $this->getGenres($json);
        if (count($genre)) {
            $result['genre'] = $genre;
        }

        return [QueryResult::createSimple($this, $field, $value, $json, $result)];
    }
    
    private function getGenres(array $json): array
    {
        $genre = [];
        foreach ($json as $key => $content) {
            if (strpos($key, 'subjectcategorydescription') === 0) {
                $genre[] = $content;
                continue;
            }
            if ($key === 'themes' && !empty($content)) {
                $genre[] = $content;
            }
        }
        
        return $genre;
    }

    public function getCode(): string
    {
        return 'randomhouse';
    }

    public static function getLabel(): string
    {
        return 'Random House';
    }
}
