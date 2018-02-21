<?php
/**
  * @author  MacFJA
  * @license MIT
  */
namespace App\Worker\Query\Transformer;

use JsonPath\JsonObject;

abstract class AbstractPathTransformer implements PathTransformerInterface
{
    /** @var  string */
    protected $inputPath;
    /** @var  string */
    protected $outputPath;
    /** @var  bool */
    protected $isArray;

    /**
     * AbstractPathTransformer constructor.
     *
     * @param string $inputPath
     * @param string $outputPath
     * @param bool   $isArray
     */
    public function __construct(string $inputPath, string $outputPath, bool $isArray = false)
    {
        $this->inputPath = $inputPath;
        $this->outputPath = $outputPath;
        $this->isArray = $isArray;
    }

    /**
     * @param mixed      $input
     * @param string     $inputPath
     * @param JsonObject $output
     * @param string     $outputPath
     * @param bool       $isArray
     * @return JsonObject
     */
    public static function run(
        $input,
        string $inputPath,
        JsonObject $output,
        string $outputPath,
        bool $isArray = false
    ): JsonObject {
        $instance = new static($inputPath, $outputPath, $isArray);
        return $instance->apply($input, $output);
    }

    /**
     * @param mixed  $input
     * @param string $inputPath
     * @param bool   $isArray
     * @return mixed
     */
    public static function getValue($input, string $inputPath, bool $isArray = false)
    {
        $instance = new static($inputPath, '$.x', $isArray);
        return $instance->get($input);
    }
}
