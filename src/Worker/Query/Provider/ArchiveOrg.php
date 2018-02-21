<?php
/**
  * @author  MacFJA
  * @license MIT
  */
namespace App\Worker\Query\Provider;

use App\Worker\Query\QueryResult;
use GuzzleHttp\Client;

class ArchiveOrg extends BaseIsbn
{
    use OPDSParserTrait;

    const API_PATTERN = 'https://bookserver.archive.org/catalog/opensearch?q=%s';
    /**
     * @param string $field
     * @param string $value
     * @return QueryResult[]
     */
    public function search(string $field, string $value): array
    {
        $client = new Client();
        $response = $client->get(sprintf(static::API_PATTERN, urlencode($value)));

        return $this->parseAtom($this, [$field=>$value], $response->getBody()->getContents());
    }

    public function getCode(): string
    {
        return 'archiveorg';
    }

    public static function getLabel(): string
    {
        return 'Archive.org';
    }
}
