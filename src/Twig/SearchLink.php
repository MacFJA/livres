<?php
/**
 * @author  MacFJA
 * @license MIT
 */
namespace App\Twig;

class SearchLink extends \Twig_Extension
{
    public function getFunctions()
    {
        return [
            new \Twig_Function('searchCriteria', [$this, 'buildSearchCriteria'])
        ];
    }

    public function buildSearchCriteria(string $field, string $value): string
    {
        return base64_encode(json_encode([$field => $value]));
    }
}
