<?php
/**
  * @author  MacFJA
  * @license MIT
  */
namespace App\Worker\Query\Transformer;

use JsonPath\JsonObject;

class PathTransformerGroup
{
    /** @var PathTransformerInterface[] */
    protected $transformers = [];

    public function addTransformer(PathTransformerInterface $transformer): PathTransformerGroup
    {
        $this->transformers[] = $transformer;
        return $this;
    }

    /**
     * @param mixed $input
     * @return array
     */
    public function apply($input) : array
    {
        $output = new JsonObject([]);
        foreach ($this->transformers as $transformer) {
            $output = $transformer->apply($input, $output);
        }

        return $output->getValue();
    }
}
