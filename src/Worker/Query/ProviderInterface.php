<?php
/**
  * @author  MacFJA
  * @license MIT
  */
namespace App\Worker\Query;

interface ProviderInterface
{
    const FIELD_INTERNAL = 'internal';
    public function canSearch(string $field): bool ;

    /**
     * @param string $field
     * @param string $value
     * @return QueryResult[]
     */
    public function search(string $field, string $value): array;
    /**
     * @param array<string,string> $terms
     * @return QueryResult[]
     */
    public function searchComposite(array $terms): array;
    public function getCode(): string ;
    public static function getLabel(): string ;
}
