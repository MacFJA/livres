<?php
/**
  * @author  MacFJA
  * @license MIT
  */
namespace App\Worker\Query\Provider;

use App\Worker\Query\QueryResult;
use GuzzleHttp\Client;

class AntoineOnline extends BaseIsbn
{
    use HtmlGetterTrait;

    const WEBPAGE_SEARCH_PATTERN = 'http://www.antoineonline.com/listing.aspx?q=%s&type=1';
    const WEBPAGE_BASE_URL = 'http://www.antoineonline.com';

    /**
     * @param string $field
     * @param string $value
     * @return QueryResult[]
     */
    public function search(string $field, string $value): array
    {
        $client = new Client();
        $response = $client->post(
            sprintf(static::WEBPAGE_SEARCH_PATTERN, urlencode($value)),
            ['allow_redirects' => false]
        );

        if ($response->getStatusCode() != '302') {
            return [];
        }
        $location = $response->getHeader('location');
        $location = reset($location);

        $xml = $this->getWebpageUrlAsDom(static::WEBPAGE_BASE_URL.$location);

        $allMeta = $xml->getElementsByTagName('meta');
        $resultDiv = $xml->getElementById('ctl00_cph1_ProductMainPage');
        $allInput = $resultDiv->getElementsByTagName('input');

        $result = ['antoineonline_link' => static::WEBPAGE_BASE_URL.$location];
        $result = $this->handleMeta($result, $allMeta);
        $result = $this->handleInputs($result, $allInput);
        
        return [QueryResult::createSimple($this, $field, $value, $xml, $result)];
    }

    private function handleInputs(array $result, \DOMNodeList $allInput): array
    {
        for ($index = 0; $index < $allInput->length; $index++) {
            /** @var \DOMElement $input */
            $input = $allInput->item($index);
            $value = $input->getAttribute('value');
            if ($input->getAttribute('class') == 'author' && !empty($value)) {
                $result['author'] = [$value];
            }
            if ($input->getAttribute('class') == 'product-title') {
                $result['title'] = $value;
            }
        }
        
        return $result;
    }

    private function handleMeta(array $result, \DOMNodeList $allMeta): array
    {
        for ($index = 0; $index < $allMeta->length; $index++) {
            /** @var \DOMElement $meta */
            $meta = $allMeta->item($index);

            if (!$meta->hasAttribute('name')) {
                continue;
            }

            $property = $meta->getAttribute('name');
            $content = $meta->getAttribute('content');

            switch ($property) {
                case 'og:image':
                    $result['cover'] = $content;
                    break;
                case 'og:title':
                    $result['title'] = $content;
                    break;
            }
        }
        
        return $result;
    }

    public function getCode(): string
    {
        return 'antoineonline';
    }

    public static function getLabel(): string
    {
        return 'AntoineOnline (HTML)';
    }
}
