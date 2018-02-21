<?php
/**
  * @author  MacFJA
  * @license MIT
  */
namespace App\Worker\Query\Transformer;

use App\Worker\Query\Transformer\AbstractPathTransformer;
use JsonPath\JsonObject;

class JsonPathTransformer extends AbstractPathTransformer
{
    /**
     * @param JsonObject|mixed $input
     * @param JsonObject       $output
     * @return JsonObject
     */
    public function apply($input, JsonObject $output): JsonObject
    {
        if (!($input instanceof JsonObject)) {
            return $output;
        }
        
        $output->set($this->outputPath, $this->get($input));
        
        return $output;
    }

    /**
     * @param JsonObject|mixed $input
     * @return mixed|null|string
     */
    public function get($input)
    {
        if (!($input instanceof JsonObject)) {
            return null;
        }
        
        $data = $input->get($this->inputPath);

        if ($data === false) {
            return null;
        }

        return $this->isArray ? $data : implode(', ', $data);
    }
}
