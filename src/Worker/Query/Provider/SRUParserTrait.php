<?php
/**
  * @author  MacFJA
  * @license MIT
  */
namespace App\Worker\Query\Provider;

use App\Worker\Query\ProviderInterface;
use App\Worker\Query\QueryResult;
use App\Worker\Query\Transformer\PathTransformerGroup;
use App\Worker\Query\Transformer\XPathTransformer as XPT;

trait SRUParserTrait
{
    protected function parseSRU(ProviderInterface $instance, string $source, array $terms) : array
    {
        $source = preg_replace('/xmlns="[^"]+"/', '', $source);
        $xml = simplexml_load_string($source);

        $transformation = (new PathTransformerGroup())
            ->addTransformer(new XPT('./titleInfo/title/text()', '$.title'))
            ->addTransformer(new XPT('./titleInfo/subTitle/text()', '$.subtitle'))
            ->addTransformer(new XPT('./originInfo[@eventType="publisher"]/publisher/text()', '$.publisher'))
            ->addTransformer(new XPT('./name/namePart/text()', '$.author', true))
            ->addTransformer(new XPT('./physicalDescription/form[authority="marcform"]/text()', '$.format'))
            ->addTransformer(new XPT('./identifier[@type="isbn"]/text()', '$.isbn'))
            ->addTransformer(new XPT('./language/languageTerm/text()', '$.language'))
            ->addTransformer(new XPT('./genre/text()', '$.genre', true))
        ;

        $results = [];
        /** @var \SimpleXMLElement $mods */
        foreach ($xml->children() as $mods) {
            $mods->registerXPathNamespace('n', 'http://www.loc.gov/mods/v3');
            $normalized = $transformation->apply($mods);


            $date = XPT::getValue($mods, '/originInfo[@eventType="publisher"]/dateIssued/text()');
            if ($date != null) {
                $date = \DateTime::createFromFormat('Y', $date);
            }
            $normalized['publicationDate'] = $date;
            $results[] = QueryResult::createComposite($instance, $terms, $mods, $normalized);
        }

        return $results;
    }
}
