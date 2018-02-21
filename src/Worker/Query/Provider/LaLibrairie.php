<?php
/**
  * @author  MacFJA
  * @license MIT
  */
namespace App\Worker\Query\Provider;

use App\Worker\Query\QueryResult;
use GuzzleHttp\Client;

class LaLibrairie extends BaseIsbn
{
    use HtmlGetterTrait;

    const WEBPAGE_URL = 'https://www.lalibrairie.com/livres/recherche.html';
    const WEBPAGE_BASE_URL = 'https://www.lalibrairie.com';

    /**
     * @param string $field
     * @param string $value
     * @return QueryResult[]
     */
    public function search(string $field, string $value): array
    {
        $client = new Client();
        $response = $client->request(
            'POST',
            static::WEBPAGE_URL,
            [
                'allow_redirects' => false,
                'form_params' => [
                    'rapid-search' => urlencode($value)
                ]
            ]
        );

        if (!$response->hasHeader('location')) {
            return [];
        }
        $location = $response->getHeader('location');
        $location = reset($location);

        if ($location === '/') {
            return [];
        }

        $xml = $this->getWebpageUrlAsXml(static::WEBPAGE_BASE_URL.$location);

        $allMeta = $xml->xpath('//*[local-name()="meta"]');
        $itemProperties = $xml->xpath('//*[@itemprop]');

        $result = $this->handleMeta(['author' => []], $allMeta);
        $result = $this->handleItemProperties($result, $itemProperties);

        return [QueryResult::createSimple($this, $field, $value, $xml, $result)];
    }

    /**
     * @param array $result
     * @param \SimpleXMLElement[] $allMeta
     * @return array
     */
    private function handleMeta(array $result, array $allMeta) : array
    {
        $metaMapping = [
            'og:image' => 'cover',
            'og:url' => 'lalibrairie_link',
            'og:title' => 'title'
        ];
        foreach ($allMeta as $meta) {
            $property = (string) $meta['property'];
            $content = (string) $meta['content'];

            if (array_key_exists($property, $metaMapping)) {
                $result[$metaMapping[$property]] = $content;
            }
        }

        return $result;
    }

    /**
     * @param array $result
     * @param \SimpleXMLElement[] $itemProperties
     * @return array
     */
    private function handleItemProperties(array $result, array $itemProperties) : array
    {
        $itemPropertyMapping = [
            'price' => null,
            'priceCurrency' => null,
            'name' => 'title',
            'editor' => 'publisher',
            'numberOfPages' => 'pages',
            'gtin13' => 'isbn'
        ];
        foreach ($itemProperties as $itemProperty) {
            $property = (string) $itemProperty['itemprop'];

            if (array_key_exists($property, $itemPropertyMapping) && $itemPropertyMapping[$property] === null) {
                continue;
            }

            switch ($property) {
                case 'author':
                    $result['author'][] = (string) $itemProperty;
                    break;
                case 'image':
                    $result['cover'] = (string) $itemProperty['src'];
                    break;
                case 'weight':
                    $result['weight'] = (string) $itemProperty . 'g';
                    break;
                case 'datePublished':
                    $result['publicationDate'] = \DateTime::createFromFormat('d/m/Y', (string) $itemProperty);
                    break;
                default:
                    $result[$itemPropertyMapping[$property]??$property] = (string) $itemProperty;
            }
        }

        return $result;
    }

    public function getCode(): string
    {
        return 'lalibrairie';
    }

    public static function getLabel(): string
    {
        return 'Lalibrairie.com (HTML)';
    }
}
