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

class ISBNDb extends BaseIsbn
{
    /** @var string */
    protected $apiKey;

    /**
     * ISBNDb constructor.
     *
     * @param string $apiKey API Key
     */
    public function __construct(string $apiKey)
    {
        $this->apiKey = $apiKey;
    }

    /**
     * @param string $field
     * @param string $value
     * @return QueryResult[]
     */
    public function search(string $field, string $value): array
    {
        $client = new Client();
        $response = $client->get('http://api.isbndb.com/book/'.urlencode($value), [
            'headers' => ['x-api-key' => $this->apiKey]
        ]);

        if ($response->getStatusCode() != 200) {
            return [];
        }

        $json = \GuzzleHttp\json_decode($response->getBody()->getContents());

        $transformation = new PathTransformerGroup();
        $transformation
            ->addTransformer(new JPT('$.book.publisher', '$.publisher'))
            ->addTransformer(new JPT('$.book.language', '$.language'))
            ->addTransformer(new JPT('$.book.title_long', '$.title'))
            ->addTransformer(new JPT('$.book.isbn13', '$.isbn'))
            ->addTransformer(new JPT('$.book.authors', '$.author', true))
            ->addTransformer(new JPT('$.book.dimensions', '$.dimension'))
        ;
        
        $jsonObject = new JsonObject($json);

        return [QueryResult::createSimple($this, $field, $value, $json, $transformation->apply($jsonObject))];
    }

    public function getCode(): string
    {
        return 'isbndb';
    }

    public static function getLabel(): string
    {
        return 'ISBNdb';
    }
}
