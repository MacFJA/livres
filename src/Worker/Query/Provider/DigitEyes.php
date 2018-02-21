<?php
/**
  * @author  MacFJA
  * @license MIT
  */
namespace App\Worker\Query\Provider;

use App\Worker\Query\QueryResult;
use GuzzleHttp\Client;

class DigitEyes extends BaseIsbn
{
    const API_PATTERN = 'http://digit-eyes.com/gtin/v2_0/' .
        '?upc_code=%s' .
        '&app_key=%s' .
        '&signature=%s' .
        '&language=%s' .
        '&field_names=%s';
    /**
     * @var string
     */
    private $apiKey;
    /**
     * @var string
     */
    private $appCode;

    /**
     * DigitEyes constructor.
     *
     * @param string $apiKey
     * @param string $appCode
     */
    public function __construct(string $apiKey, string $appCode)
    {
        $this->apiKey = $apiKey;
        $this->appCode = $appCode;
    }


    /**
     * @param string $field
     * @param string $value
     * @return QueryResult[]
     */
    public function search(string $field, string $value): array
    {
        $client = new Client();
        $response = $client->get(sprintf(
            static::API_PATTERN,
            $value,
            $this->appCode,
            $this->getSignature($value),
            'en',
            'description,image,thumbnail,categories'
        ));

        if ($response->getStatusCode() != 200) {
            return [];
        }

        $json = \GuzzleHttp\json_decode($response->getBody()->getContents(), true);
        $results = [];

        foreach ($json as $item) {
            $results[] = QueryResult::createSimple($this, $field, $value, $item, [
                'cover' => $item['image']??$item['thumbnail']??null,
                'title' => $item['description'],
                'genre' => $item['catgeories']
            ]);
        }

        return $results;
    }

    public function getCode(): string
    {
        return 'digit-eyes';
    }

    public static function getLabel(): string
    {
        return 'DigitEyes';
    }

    private function getSignature(string $ean): string
    {
        return base64_encode(hash_hmac('sha1', $ean, $this->apiKey, true));
    }
}
