<?php
/**
  * @author  MacFJA
  * @license MIT
  */
namespace App\Worker\Query\Provider;

abstract class BaseAggregate extends Base
{
    protected function getPerRequestDelay() : int
    {
        return 1000;
    }
    protected function doCompositeSearch(array $terms) : array
    {
        $results = [];
        foreach ($terms as $field => $value) {
            $results = array_merge($results, $this->search($field, $value));
            usleep($this->getPerRequestDelay());
        }

        return array_filter($results);
    }
}
