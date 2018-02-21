<?php
/**
  * @author  MacFJA
  * @license MIT
  */
namespace App\Worker\Query\Provider;

use App\Worker\Query\QueryResult;

class EuroBuch extends BaseIsbn
{
    use HtmlGetterTrait;

    const WEBPAGE_PATTERN = 'https://fr.eurobuch.ch/livre/isbn/%s.html';
    /**
     * @param string $field
     * @param string $value
     * @return QueryResult[]
     */
    public function search(string $field, string $value): array
    {
        $xml = $this->getWebpageUrlAsDom(sprintf(static::WEBPAGE_PATTERN, urlencode($value)));

        if ($xml->getElementById('book_details_content') == null) {
            return [];
        }

        $cover = $xml->getElementById('book_details_cover')->getElementsByTagName('img');
        if ($cover->length > 0) {
            /** @var \DOMElement $cover */
            $cover = $cover->item(0);
            $cover = $cover->getAttribute('src');
        } else {
            $cover = null;
        }
        return [
            QueryResult::createSimple($this, $field, $value, $xml->getElementById('book_details_content'), [
                'title' => $xml->getElementById('book_details_title')->textContent,
                'cover' => $cover,
                'author' => [$xml->getElementById('book_details_author')->textContent],
                'description' => $this->getInnerText($xml->getElementById('book_details_description')),
                'eurobuch_link' => sprintf(static::WEBPAGE_PATTERN, $value),
                'isbn' => $value
            ])
        ];
    }

    public function getCode(): string
    {
        return 'eurobuch';
    }

    public static function getLabel(): string
    {
        return 'EuroBuch.ch (HTML)';
    }
}
