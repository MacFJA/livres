<?php
/**
  * @author  MacFJA
  * @license MIT
  */
namespace App\Worker\Query\Provider;

use App\Worker\Query\ProviderInterface;
use App\Worker\Query\QueryResult;

abstract class Base implements ProviderInterface
{
    protected function filterSearchTerms(array $terms) : array
    {
        return array_filter($terms, function (string $field): bool {
            return $this->canSearch($field);
        }, ARRAY_FILTER_USE_KEY);
    }

    public function canSearch(string $field): bool
    {
        return in_array(strtolower($field), $this->getSearchableField(), true);
    }
    
    abstract protected function getSearchableField() : array;


    public function searchComposite(array $terms): array
    {
        $filtered = $this->filterSearchTerms($terms);
        
        if (count($filtered) == 0) {
            throw new \InvalidArgumentException(
                'This provider can search with the provided terms (was: "'.implode(', ', array_keys($terms)).'")'
            );
        }
        
        if (count($filtered) == 1) {
            reset($filtered);
            return $this->search(key($filtered), current($filtered));
        }
        
        return $this->doCompositeSearch($filtered);
    }

    /**
     * @param array<string, string> $terms
     * @return QueryResult[]
     */
    abstract protected function doCompositeSearch(array $terms) : array;
}
