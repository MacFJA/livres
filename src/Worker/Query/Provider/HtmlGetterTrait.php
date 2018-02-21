<?php
/**
 * @author  MacFJA
 * @license MIT
 */
namespace App\Worker\Query\Provider;

use GuzzleHttp\Client;
use Masterminds\HTML5;

trait HtmlGetterTrait
{
    protected function getWebpageUrlAsXml(string $url): \SimpleXMLElement
    {
        $xml = $this->getWebpageUrlAsDom($url);
        $xmlString = $xml->saveXML();

        return simplexml_load_string($xmlString);
    }

    protected function getWebpageUrlAsDom(string $url): \DOMDocument
    {
        $client = new Client();
        $response = $client->get($url);

        $html5 = new HTML5();

        return $html5->loadHTML($response->getBody()->getContents());
    }

    private function getInnerTextXml(\SimpleXMLElement $node): string
    {
        if ($node->count() === 0) {
            return (string)$node;
        }

        return array_reduce(
            iterator_to_array($node->children()),
            function (string $carry, \SimpleXMLElement $item): string {
                return $carry . $this->getInnerTextXml($item);
            },
            ''
        );
    }
    
    private function getInnerTextDom(\DOMNode $node): string
    {
        if (!$node->hasChildNodes()) {
            return trim($node->textContent);
        }
        $result = [];
        for ($index = 0; $index < $node->childNodes->length; $index++) {
            $item = $node->childNodes->item($index);
            if ($item === null) {
                continue;
            }
            $result[] = $this->getInnerTextDom($item);
        }

        return implode("\n", array_filter($result));
    }
    
    /**
     * @param \DOMNode|\SimpleXMLElement $node
     * @return string
     */
    protected function getInnerText($node): string
    {
        if ($node instanceof \DOMNode) {
            return $this->getInnerTextDom($node);
        }

        if ($node instanceof \SimpleXMLElement) {
            return $this->getInnerTextXml($node);
        }

        return '';
    }
}
