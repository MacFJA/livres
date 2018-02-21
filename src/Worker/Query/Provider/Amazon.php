<?php
/**
 * @author  MacFJA
 * @license MIT
 */
namespace App\Worker\Query\Provider;

use App\Worker\Query\QueryResult;
use GuzzleHttp\Client;

class Amazon extends Base
{
    const API_BASE_URL_PATTERN = 'http://webservices.amazon.%s/onca/xml' .
        '?AWSAccessKeyId=%s' .
        '&AssociateTag=%s' .
        '&Service=AWSECommerceService' .
        '&ResponseGroup=ItemAttributes,Images' .
        '&SearchIndex=Books&';
    const API_EAN_URL_PATTERN = 'Operation=ItemLookup&IdType=EAN&ItemId=%s';
    const API_ISBN_URL_PATTERN = 'Operation=ItemLookup&IdType=ISBN&ItemId=%s';
    const API_ASIN_URL_PATTERN = 'Operation=ItemLookup&IdType=ASIN&ItemId=%s';
    const API_SEARCH_URL_PATTERN = 'Operation=ItemSearch&IncludeReviewsSummary=false&%s';

    /** @var  string */
    protected $accessKey;
    /** @var  string */
    protected $associateTag;
    /** @var string[] */
    protected $countryDomains = ['fr', 'us', 'com', 'de', 'ca', 'es', 'co.jp', 'co.uk'];

    /**
     * Amazon constructor.
     *
     * @param string $accessKey    The access key
     * @param string $associateTag The associate tag
     */
    public function __construct(string $accessKey, string $associateTag)
    {
        $this->accessKey = $accessKey;
        $this->associateTag = $associateTag;
    }


    protected function getSearchableField() : array
    {
        return ['isbn', 'ean', 'title', 'publisher', 'author', 'asin', self::FIELD_INTERNAL];
    }

    protected function doCompositeSearch(array $terms) : array
    {
        $params = [];
        foreach ($terms as $field => $value) {
            $params[] = $this->getUrlSearchTerm($field, $value);
        }
        $params = array_filter($params);

        return $this->doRequest(sprintf(self::API_SEARCH_URL_PATTERN, implode('&', $params)), $terms);
    }

    /**
     * @param string $field
     * @param string $value
     * @return string
     * @throws \InvalidArgumentException
     */
    protected function getUrlSearchTerm(string $field, string $value) : string
    {
        $mapping = [
            'title'     => 'Title',
            'publisher' => 'Publisher',
            'author'    => 'Author'
        ];

        if (!array_key_exists($field, $mapping)) {
            throw new \InvalidArgumentException('$field is not a value search key');
        }

        return urlencode($mapping[$field]) . '=' . urlencode($value);
    }

    /**
     * @param string $field
     * @param string $value
     * @return QueryResult[]
     */
    public function search(string $field, string $value): array
    {
        switch (strtolower($field)) {
            case 'isbn':
                return $this->doRequest(sprintf(self::API_ISBN_URL_PATTERN, $value), [$field => $value]);
            case 'ean':
                return $this->doRequest(sprintf(self::API_EAN_URL_PATTERN, $value), [$field => $value]);
            case 'asin':
            case self::FIELD_INTERNAL:
                return $this->doRequest(sprintf(self::API_ASIN_URL_PATTERN, $value), [$field => $value]);
            default:
                return $this->doRequest(
                    sprintf(self::API_SEARCH_URL_PATTERN, $this->getUrlSearchTerm($field, $value)),
                    [$field => $value]
                );
        }
    }

    private function doRequest(string $queryUrl, array $terms) : array
    {
        $client = new Client();
        $results = [];
        foreach ($this->countryDomains as $domain) {
            $response = $client->get(
                sprintf(self::API_BASE_URL_PATTERN, $domain, $this->accessKey, $this->associateTag) .
                $queryUrl
            );
            if (!isset($response->Item) || $response->Item == null) {
                continue;
            }


            /** @var \SimpleXMLElement $item */
            foreach ($response->Item->children() as $item) {
                $results[] = QueryResult::createComposite($this, $terms, $response, [
                    'author'          => [(string)$item->ItemAttributes->Author],
                    'amazon_id'       => (string)$item->ASIN,
                    'isbn'            => (string)$item->ItemAttributes->EAN,
                    'format'          => (string)$item->ItemAttributes->Binding,
                    'genre'           => [(string)$item->ItemAttributes->Genre],
                    'pages'           => (int)$item->ItemAttributes->NumberOfPages,
                    'publisher'       => (string)$item->ItemAttributes->Publisher,
                    'publicationDate' => \DateTime::createFromFormat(
                        'Y-m-d',
                        (string)$item->ItemAttributes->PublicationDate
                    ),
                    'title'           => (string)$item->ItemAttributes->Title,
                    'cover'           => (string)$item->Images->LargeImage->URL,
                    'amazon_link'     => (string)$item->DetailPageURL,
                ]);
            }
        }

        return array_filter($results);
    }

    public function getCode(): string
    {
        return 'amazon';
    }

    public static function getLabel(): string
    {
        return 'Amazon';
    }
}
