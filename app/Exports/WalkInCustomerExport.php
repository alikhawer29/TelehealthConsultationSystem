<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class WalkInCustomerExport implements FromCollection, ShouldAutoSize, WithStyles
{
    protected $records;
    protected $businessName;
    protected $address;
    protected $phone;
    protected $date;

    public function __construct($records, $businessName, $address, $phone, $date)
    {
        $this->records = $records;
        $this->businessName = $businessName;
        $this->address = $address;
        $this->phone = $phone;
        $this->date = $date;
    }

    public function collection()
    {
        $finalArray = [
            [''], // Empty row
            ['Walk-In Customer Report'], // Title
            ['Business Name:', $this->businessName],
            ['Address:', $this->address],
            ['Phone:', $this->phone],
            ['Run Date:', $this->date],
            [''], // Empty row before the table
            $this->headings(),
        ];

        foreach ($this->records as $record) {
            $nationality = $record->nationalities ? $record->nationalities->name : 'N/A';
            $finalArray[] = [
                $record->customer_name,
                $record->mobile_number,
                $record->telephone_number,
                $nationality,
                $record->id_type,
                $record->id_number,
                $record->expiry_date,
            ];
        }

        return collect($finalArray);
    }

    public function headings(): array
    {
        return [
            'Customer Name',
            'Mobile Number',
            'Telephone Number',
            'Nationality',
            'Id Type',
            'Id Number',
            'Id Expiry Date',
        ];
    }

    public function styles(Worksheet $sheet)
    {
        // Title Style with Dark Blue Background
        $sheet->mergeCells('A2:G2');
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
