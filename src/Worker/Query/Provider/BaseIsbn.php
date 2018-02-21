<?php
/**
  * @author  MacFJA
  * @license MIT
  */
namespace App\Worker\Query\Provider;

/**
 * Class BaseIsbn
 *
 * @package App\Worker\Query\Provider
 * @SuppressWarnings(PHPMD.NumberOfChildren)
 */
abstract class BaseIsbn extends Base
{
    protected function getSearchableField() : array
    {
        return ['isbn', 'ean'];
    }

    protected function doCompositeSearch(array $terms) : array
    {
        return $this->search('isbn', reset($terms));
    }
}
