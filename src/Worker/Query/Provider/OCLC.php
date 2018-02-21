<?php
/**
  * @author  MacFJA
  * @license MIT
  */
namespace App\Worker\Query\Provider;

use App\Worker\Query\QueryResult;
use GuzzleHttp\Client;
use Psr\Http\Message\ResponseInterface;

class OCLC extends Base
{
    const API_ID_URL_PATTERN = 'http://classify.oclc.org/classify2/Classify?%s=%s&summary=true';
    const API_SEARCH_URL_PATTERN = 'http://classify.oclc.org/classify2/Classify?%s&summary=true';

    public function search(string $field, string $value): array
    {
        $client = new Client();

        switch (strtolower($field)) {
            case 'isbn':
            case 'ean':
                $url = sprintf(self::API_ID_URL_PATTERN, 'isbn', urlencode($value));
                break;
            case self::FIELD_INTERNAL:
            case 'oclc':
                $url = sprintf(self::API_ID_URL_PATTERN, 'oclc', urlencode($value));
                break;
            default:
                $url = sprintf(self::API_SEARCH_URL_PATTERN, urlencode(strtolower($field)).'='.urlencode($value));
        }

        return $this->parseResult($client->get($url), [$field => $value]);
    }

    public function getCode(): string
    {
        return 'oclc';
    }

    public static function getLabel(): string
    {
        return 'OCLC';
    }

    protected function doCompositeSearch(array $terms) : array
    {
        $client = new Client();

        $url = sprintf(self::API_SEARCH_URL_PATTERN, http_build_query($terms));

        return $this->parseResult($client->get($url), $terms);
    }

    protected function parseResult(ResponseInterface $response, array $terms) : array
    {
        $xml = simplexml_load_string($response->getBody()->getContents());

        $results = [];
        foreach ($xml->xpath('//work') as $work) {
            $authors = explode(' | ', $work['author']);

            $results[] = QueryResult::createComposite($this, $terms, $work, [
                'author' => array_filter($authors, function (string $author): bool {
                    return (
                        strpos($author, 'Illustrator') === false &&
                        strpos($author, 'Translator') === false
                    ) || strpos($author, 'Author') !== false;
                }),
                'illustrator' => array_filter($authors, function (string $author): bool {
                    return strpos($author, 'Illustrator') !== false;
                }),
                'translator' => array_filter($authors, function (string $author): bool {
                    return strpos($author, 'Translator') !== false;
                }),
                'format' => $work['format'],
                'title' => $work['title'],
            ]);
        }

        return $results;
    }

    protected function getSearchableField() : array
    {
        return ['isbn','ean','oclc',self::FIELD_INTERNAL,'author','title'];
    }
}
