<?php
/**
  * @author  MacFJA
  * @license MIT
  */
namespace App\Worker\Query;

class QueryResult
{
    /** @var ProviderInterface  */
    protected $query;
    /** @var array<string, string>  */
    protected $searchTerms;
    /** @var mixed  */
    protected $rawResult;
    /** @var array<string,mixed>  */
    protected $formattedResult;

    /**
     * QueryResult constructor.
     *
     * @param ProviderInterface    $query
     * @param array<string,string> $searchTerms
     * @param mixed                $rawResult
     * @param array<string,mixed>  $formattedResult
     */
    public function __construct(ProviderInterface $query, array $searchTerms, $rawResult, array $formattedResult)
    {
        $this->query = $query;
        $this->searchTerms = $searchTerms;
        $this->rawResult = $rawResult;
        $this->formattedResult = $formattedResult;
    }

    /**
     * @param ProviderInterface $query
     * @param array             $searchTerms
     * @param mixed             $rawResult
     * @param array             $formattedResult
     * @return QueryResult
     */
    public static function createComposite(
        ProviderInterface $query,
        array $searchTerms,
        $rawResult,
        array $formattedResult
    ) : QueryResult {
        return new static($query, $searchTerms, $rawResult, $formattedResult);
    }

    /**
     * @param ProviderInterface $query
     * @param string            $searchField
     * @param string            $searchValue
     * @param mixed             $rawResult
     * @param array             $formattedResult
     * @return QueryResult
     */
    public static function createSimple(
        ProviderInterface $query,
        string $searchField,
        string $searchValue,
        $rawResult,
        array $formattedResult
    ) : QueryResult {
        return static::createComposite($query, [$searchField => $searchValue], $rawResult, $formattedResult);
    }

    public function getNormalized(bool $includeEmpty) : array
    {
        $empty = [
            'isbn' => null,
            'title' => null,
            'author' => null,
            'pages' => null,
            'serie' => null,
            'sortTitle' => null,
            'publisher' => null,
            'illustrator' => null,
            'translator' => null,
            'genre' => null,
            'publicationDate' => null,
            'edition' => null,
            'editor' => null,
            'format' => null,
            'dimension' => null,
            'keywords' => null,
            'addedAt' => null,
            'cover' => null,
        ];

        return $includeEmpty? $this->formattedResult+$empty:array_filter($this->formattedResult);
    }

    public function getProviderName() : string
    {
        return $this->query->getLabel();
    }
}
