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

trait OPDSParserTrait
{
    /**
     * @param ProviderInterface $provider
     * @param array             $terms
     * @param string            $response
     * @return QueryResult[]
     */
    protected function parseAtom(ProviderInterface $provider, array $terms, string $response): array
    {
        $atom = simplexml_load_string($response);

        $nodes = $atom->xpath('//*[local-name()="entry"]');
        $group = new PathTransformerGroup();
        $group
            ->addTransformer(new XPT('//*[name()="title"]/text()', '$.title'))
            ->addTransformer(new XPT('//*[name()="dcterms:identifier"]/text()', '$.isbn'))
            ->addTransformer(new XPT('//*[name()="author"]/*[name()="name"]/text()', '$.author', true))
            ->addTransformer(new XPT('//*[name()="dcterms:language"]/text()', '$.language'))
            ->addTransformer(new XPT('//*[name()="dcterms:publisher"]/text()', '$.publisher'))
            ->addTransformer(new XPT('//*[name()="dcterms:extent"]/text()', '$.pages'))
            ->addTransformer(new XPT('//*[name()="category"]/@label', '$.genre', true))
            ->addTransformer(new XPT('//*[name()="summeary"]/text()', '$.summary'))
            ->addTransformer(new XPT(
                '//*[name()="link" and starts-with(@type,"image") and not(contains(@rel,"thumbnail"))]/@href',
                '$.cover'
            ))
            ->addTransformer(new XPT('//*[name()="id"]/text()', '$.opds_link', true))
        ;

        $results = [];

        foreach ($nodes as $node) {
            $result = $group->apply($node);
            $date = XPT::getValue($node, '//*[name()="published"]/text()');
            $date = \DateTime::createFromFormat(\DateTime::ATOM, $date);
            $result['publicationDate'] = $date;
            $result['pages'] = (int) $result['pages'];

            $results[] = QueryResult::createComposite($provider, $terms, $node, $result);
        }
        return $results;
    }
}
