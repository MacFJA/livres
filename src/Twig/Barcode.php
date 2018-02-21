<?php
/**
  * @author  MacFJA
  * @license MIT
  */
namespace App\Twig;

use Picqer\Barcode\BarcodeGeneratorSVG;

class Barcode extends \Twig_Extension
{
    public function __construct()
    {
    }

    public function getFilters()
    {
        return [
            new \Twig_Filter('barcode', [$this, 'getBarcode']),
            new \Twig_Filter('isbn', [$this, 'getIsbn']),
        ];
    }

    public function getBarcode(string $code, string $type = 'ean13', int $widthFactor = 2, int $height = 30): string
    {
        $svg = new BarcodeGeneratorSVG();
        $result = $svg->getBarcode($code, strtoupper($type), $widthFactor, $height);

        // remove the id (avoid duplicate id)
        $result = str_replace(' id="bars" ', ' ', $result);

        // remove the firsts 2 lines
        $lines = explode("\n", $result);
        array_shift($lines);
        array_shift($lines);
        return implode("\n", $lines);
    }

    public function getIsbn(string $code, int $widthFactor = 2, int $height = 30): string
    {
        if (strlen($code) != 13 || !ctype_digit($code)) {
            return '';
        }

        return $this->getBarcode($code, 'EAN13', $widthFactor, $height);
    }
}
