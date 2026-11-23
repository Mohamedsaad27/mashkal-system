<?php

namespace App\Support;

use OpenSpout\Common\Entity\Row;
use OpenSpout\Common\Entity\Style\Style;
use OpenSpout\Writer\XLSX\Entity\SheetView;
use OpenSpout\Writer\XLSX\Writer;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Streams a simple right-to-left Excel sheet synchronously (no queue needed).
 */
class XlsxDownload
{
    /**
     * @param  list<string>  $headings
     * @param  iterable<list<string|int|float|null>>  $rows
     */
    public static function make(string $fileName, array $headings, iterable $rows): StreamedResponse
    {
        return response()->streamDownload(function () use ($headings, $rows): void {
            $writer = new Writer;
            $writer->openToFile('php://output');
            $writer->getCurrentSheet()->setSheetView((new SheetView)->setRightToLeft(true));

            $writer->addRow(Row::fromValues($headings, (new Style)->setFontBold()));

            foreach ($rows as $row) {
                $writer->addRow(Row::fromValues($row));
            }

            $writer->close();
        }, $fileName.'.xlsx', [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ]);
    }
}
