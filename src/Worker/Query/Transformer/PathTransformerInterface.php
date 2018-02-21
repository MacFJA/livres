<?php
/**
  * @author  MacFJA
  * @license MIT
  */
namespace App\Worker\Query\Transformer;

use JsonPath\JsonObject;

interface PathTransformerInterface
{
    public function apply($input, JsonObject $output): JsonObject;

    public function get($input);

    public static function run(
        $input,
        string $inputPath,
        JsonObject $output,
        string $outputPath,
        bool $isArray = false
    ): JsonObject;

    public static function getValue($input, string $inputPath, bool $isArray = false);
}
