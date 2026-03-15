<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class PartyLedgerExport implements FromArray, ShouldAutoSize, WithStyles
{
    protected $data;

    public function __construct($data)
    {
        $this->data = $data;
    }

    // Prepare the data for export
    public function array(): array
    {
        // Prepare the final array with custom rows (no column headings here)
        $finalArray = [
            [''], // Empty row
            [$this->data['title']], // Title
            ['Business Name:', $this->data['business_name']],
            ['Address:', $this->data['address']],
            ['Phone:', $this->data['phone']],
            ['Run Date:', $this->data['date']],
            [''], // Empty row before the table
            $this->headings(),
        ];

        // Add records directly
        foreach ($this->data['records'] as $record) {
            $finalArray[] = [
                $record->account_title,
                $record->company_name ?? '',
                $record->classifications->classification ?? '',
                $record->country_code . ' ' . $record->telephone_number,
                $record->nationalities->name ?? '',
                $record->mobile_country_code . ' ' . $record->mobile_number,
                $record->status ?? '',
            ];
        }

        return $finalArray;
    }

    // Column headings for the data
    public function headings(): array
    {
        return [
            'Account',
            'Company',
            'Classification',
            'Tel.',
            'Country',
            'Mobile No.',
            'Status',
        ];
    }

    // Apply styles to the worksheet
    public function styles(Worksheet $sheet)
    {
        // Title Style with Dark Blue Background
        $sheet->mergeCells('A2:G2'); // Merge cells for the title row
        $sheet->getStyle('A2')->getFont()->setBold(true)->setSize(16)->getColor()->setARGB('FFFFFFFF'); // White font
        $sheet->getStyle('A2')->getAlignment()->setHorizontal('center');
        $sheet->getStyle('A2:G2')->getFill()->setFillType('solid')->getStartColor()->setARGB('FF000080'); // Dark blue background

        // Header Label Styles
        $sheet->getStyle('A3:A6')->getFont()->setBold(true);
        $sheet->getStyle('A3:A6')->getAlignment()->setHorizontal('left');

        // Table Header Styles
        $sheet->getStyle('A8:G8')->getFont()->setBold(true);
        $sheet->getStyle('A8:G8')->getAlignment()->setHorizontal('left'); // Left-align headers
        $sheet->getStyle('A8:G8')->getFill()->setFillType('solid')->getStartColor()->setARGB('FF85C1E9'); // Light blue

        // Table Content Styles with Alternating Row Colors
        $highestRow = $sheet->getHighestRow();
        for ($row = 9; $row <= $highestRow; $row++) {
            // Set alignment for content
            $sheet->getStyle("A{$row}:G{$row}")->getAlignment()->setHorizontal('left');

            if ($row % 2 == 0) {
                // Even rows: Light gray background
                $sheet->getStyle("A{$row}:G{$row}")->getFill()
                    ->setFillType('solid')
                    ->getStartColor()
                    ->setARGB('ADD8E6');
            } else {
                // Odd rows: White background
                $sheet->getStyle("A{$row}:G{$row}")->getFill()
                    ->setFillType('solid')
                    ->getStartColor()
                    ->setARGB('FFFFFFFF');
            }
        }

        // Set Background Color for Title Section
        $sheet->getStyle('A3:G6')->getFill()->setFillType('solid')->getStartColor()->setARGB('FFF2F3F4');

        return [];
    }
}
