<?php
/**
  * @author  MacFJA
  * @license MIT
  */
namespace App\Worker\Query\Provider;

use App\Worker\Query\QueryResult;
use App\Worker\Query\Transformer\XPathTransformer as XPT;
use GuzzleHttp\Client;

class LibraryThing extends BaseIsbn
{
    const API_URL_PATTERN = 'http://www.librarything.com/services/rest/1.1/' .
        '?method=librarything.ck.getwork' .
        '&isbn=%s' .
        '&apikey=%s';
    /** @var string */
    protected $apiKey;

    /**
     * LibraryThing constructor.
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
        $response = $client->get(sprintf(self::API_URL_PATTERN, urlencode($value), urlencode($this->apiKey)));

        $xml = simplexml_load_string($response->getBody()->getContents());

        $results = [];
        foreach ($xml->xpath('//*[name()="item"][@type="work"]') as $work) {
            $publicationDate = XPT::getValue(
                $work,
                '//*[name()="commonknowledge"]//*[name()="field"][@type="16"]//*[name()="fact"]/text()'
            );

            if ($publicationDate != null) {
                $publicationDate = \DateTime::createFromFormat('y', $publicationDate['timestamp']);
            }
            $results[] = QueryResult::createSimple($this, $field, $value, $work, [
                'author' => [(string) $work->author],
                'title' => (string) $work->title,
                'libraything_link' => (string) $work->url,
                'publicationDate' => $publicationDate,
                'average_rating' => $work->rating
            ]);
        }

        return $results;
    }

    public function getCode(): string
    {
        return 'library-thing';
    }

    public static function getLabel(): string
    {
        return 'LibraryThing';
    }
}
