<?php
/**
 * @author  MacFJA
 * @license MIT
 */
namespace App\Worker\Export;

interface ExportInterface
{
    /**
     * @param string $output
     * @return void
     */
    public function export(string $output);
    public function getName(): string;
    public function getFormatCode(): string;
}
