<?php
/**
 * @author  MacFJA
 * @license MIT
 */
namespace App\Worker\Query\Provider;

use App\Worker\Query\QueryResult;
use Masterminds\HTML5;

class AbeBooks extends BaseIsbn
{
    use HtmlGetterTrait;
    
    const WEBPAGE_PATTERN = 'https://www.abebooks.fr/servlet/SearchResults?isbn=%s&sts=t&pt=book';
    /**
     * @param string $field
     * @param string $value
     * @return QueryResult[]
     */
    public function search(string $field, string $value): array
    {
        $xml = $this->getWebpageUrlAsXml(sprintf(static::WEBPAGE_PATTERN, $value));
        $books = $xml->xpath('//*[@itemtype="http://schema.org/Book"]');

        $results = [];

        $mapping = [
            '' => null,
            'price' => null,
            'priceCurrency' => null,
            'itemCondition' => null,
            'availability' => null,
            'name' => 'title',
            'bookFormat' => 'format',
            'about' => 'description',
            // publisher, isbn are the same
        ];

        foreach ($books as $book) {
            $allMeta = $book->xpath('.//*[local-name()="meta"]');
            $result = [];
            foreach ($allMeta as $meta) {
                $property = (string) $meta['itemprop'];
                $content = (string) $meta['content'];

                if (array_key_exists($property, $mapping) && $mapping[$property] === null) {
                    continue;
                }

                switch ($property) {
                    case 'author':
                        $result['author'] = explode(',', $content);
                        break;
                    case 'datePublished':
                        $result['publicationDate'] = \DateTime::createFromFormat('Y', $content);
                        break;
                    default:
                        $result[$mapping[$property]??$property] = $content;
                }
            }
            if (preg_match(
                '#https://pictures.abebooks.com/isbn/9782298023831-\w{2}\.\w{3,4}#',
                $book->asXML(),
                $matches
            )) {
                $result['cover'] = $matches[0];
            }
            $results[] = QueryResult::createSimple($this, $field, $value, $book, $result);
        }
        return $results;
    }

    public function getCode(): string
    {
        return 'abebooks-html';
    }

    public static function getLabel(): string
    {
        return 'AbeBooks (HTML)';
    }
}
