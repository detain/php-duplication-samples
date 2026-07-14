<?php

declare(strict_types=1);

namespace __NAMESPACE__;

final class __CLASS__
{
    public function generatePdf(array $data): string
    {
        return '<pdf>Generated PDF with ' . count($data) . ' pages</pdf>';
    }

    public function generateCsv(array $data): string
    {
        return "header1,header2\n" . count($data) . " rows";
    }
}
