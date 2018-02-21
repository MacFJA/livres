<?php
/**
  * @author  MacFJA
  * @license MIT
  */
namespace App\Worker\Query\Transformer;

use JsonPath\JsonObject;

class XPathTransformer extends AbstractPathTransformer
{
    /**
     * @param \SimpleXMLElement|mixed $input
     * @param JsonObject              $output
     * @return JsonObject
     */
    public function apply($input, JsonObject $output): JsonObject
    {
        if (!($input instanceof \SimpleXMLElement)) {
            return $output;
        }
        
        $output->set($this->outputPath, $this->get($input));
        
        return $output;
    }

    /**
     * @param \SimpleXMLElement|mixed $input
     * @return array|null|string
     */
    public function get($input)
    {
        if (!($input instanceof \SimpleXMLElement)) {
            return null;
        }

        $values = $input->xpath($this->inputPath);
        if (empty($values)) {
            return null;
        }
        $values = array_map(function (\SimpleXMLElement $item): string {
            return (string)$item;
        }, $values);

        return $this->isArray ? $values : implode(', ', $values);
    }
}
