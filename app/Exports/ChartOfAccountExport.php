<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithColumnFormatting;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;

// class ChartOfAccountExport implements FromArray, WithStyles, WithColumnFormatting
// {
//     protected $data;

//     public function __construct($data)
//     {
//         $this->data = $data;
//     }

//     public function array(): array
//     {
//         // Adding additional rows at the top
//         $recordsWithoutLevel = [];

//         foreach ($this->data['records'] as $record) {
//             // Exclude 'level' from the record and prepare the array for export
//             $recordsWithoutLevel[] = [
//                 $record['account_code'],
//                 $record['account_name'],
//                 $record['description'],
//                 $record['account_type'],
//             ];
//         }

//         return [
//             [''], // Empty row
//             ['Chart of Account Report'], // Title
//             ['Business Name:', $this->data['business_name']],
//             ['Address:', $this->data['address']],
//             ['Phone:', $this->data['phone']],
//             ['Run Date:', $this->data['date']],
//             [''], // Empty row before the table
//             $this->headings(), // Column Headings
//             ...$recordsWithoutLevel // Actual Records without 'level' column
//         ];
//     }

//     public function headings(): array
//     {
//         return ['Account', 'Title of Account', 'Description', 'Type'];
//     }

//     public function styles(Worksheet $sheet)
//     {
//         $styles = [
//             2 => ['font' => ['bold' => true, 'size' => 14], 'alignment' => ['horizontal' => 'center']],
//             3 => ['font' => ['bold' => true]],
//             4 => ['font' => ['bold' => true]],
//             5 => ['font' => ['bold' => true]],
//             6 => ['font' => ['bold' => true]],
//             8 => ['font' => ['bold' => true], 'alignment' => ['horizontal' => 'center']],
//         ];

//         // Loop through records and apply bold style if 'level' is 1
//         $rowIndex = 9; // Start from row 9, because the header rows are before this
//         foreach ($this->data['records'] as $record) {
//             if ($record['level'] == 1) {
//                 // Apply bold style to the row if level is 1
//                 $styles[$rowIndex] = ['font' => ['bold' => true]];
//             }
//             $rowIndex++;
//         }

//         return $styles;
//     }

//     public function columnFormats(): array
//     {
//         return [
//             'A' => NumberFormat::FORMAT_TEXT, // Format Account Code column as text
//         ];
//     }
// }

class ChartOfAccountExport implements FromArray, WithStyles, WithColumnFormatting, ShouldAutoSize
{
    protected $data;

    public function __construct($data)
    {
        $this->data = $data;
    }

    public function array(): array
    {
        // Adding additional rows at the top
        $recordsWithoutLevel = [];

        foreach ($this->data['records'] as $record) {
            // Exclude 'level' from the record and prepare the array for export
            $recordsWithoutLevel[] = [
                $record['account_code'],
                $record['account_name'],
                $record['description'],
                $record['account_type'],
            ];
        }

        return [
            [''], // Empty row
            ['Chart of Account Report'], // Title
            ['Business Name:', $this->data['business_name']],
            ['Address:', $this->data['address']],
            ['Phone:', $this->data['phone']],
            ['Run Date:', $this->data['date']],
            [''], // Empty row before the table
            $this->headings(), // Column Headings
            ...$recordsWithoutLevel // Actual Records without 'level' column
        ];
    }

    public function headings(): array
    {
        return ['Account', 'Title of Account', 'Description', 'Type'];
    }

    public function styles(Worksheet $sheet)
    {
        $styles = [
            2 => ['font' => ['bold' => true, 'size' => 14], 'alignment' => ['horizontal' => 'center']],
            3 => ['font' => ['bold' => true]],
            4 => ['font' => ['bold' => true]],
            5 => ['font' => ['bold' => true]],
            6 => ['font' => ['bold' => true]],
            8 => ['font' => ['bold' => true], 'alignment' => ['horizontal' => 'center']],
        ];

        // Loop through records and apply bold style if 'level' is 1 and font color if level is 3 or 4
        $rowIndex = 9; // Start from row 9, because the header rows are before this
        foreach ($this->data['records'] as $record) {
            if ($record['level'] == 1) {
                // Apply bold style to the row if level is 1
                $styles[$rowIndex] = ['font' => ['bold' => true]];
            }

            if ($record['level'] == 3) {
                // Apply orange font color if level is 3
                $styles[$rowIndex] = [
                    'font' => [
                        'color' => ['rgb' => 'FF6347'], // Red color
                    ]
                ];
            }

            if ($record['level'] == 4) {
                // Apply blue font color if level is 4
                $styles[$rowIndex] = [
                    'font' => [
                        'color' => ['rgb' => '4682B4'], // Blue color
                    ]
                ];
            }

            $rowIndex++;
        }

        return $styles;
    }

    public function columnFormats(): array
    {
        return [
            'A' => NumberFormat::FORMAT_TEXT, // Format Account Code column as text
        ];
    }
}
