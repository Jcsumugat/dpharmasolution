<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Concerns\WithCustomStartCell;
use Maatwebsite\Excel\Concerns\WithMapping;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use Carbon\Carbon;

class ReportsExport implements FromCollection, WithHeadings, WithStyles, WithTitle, WithCustomStartCell, WithMapping
{
    protected $data;
    protected $reportType;
    protected $startDate;
    protected $endDate;

    public function __construct($data, $reportType, $startDate, $endDate)
    {
        $this->data = $data;
        $this->reportType = $reportType;
        $this->startDate = $startDate;
        $this->endDate = $endDate;
    }

    /**
     * @return \Illuminate\Support\Collection
     */
    public function collection()
    {
        switch ($this->reportType) {
            case 'sales':
                return collect($this->data['sales']);
            case 'inventory':
                return collect($this->data['inventory']);
            case 'products':
                return collect($this->data['products']);
            default:
                return collect([]);
        }
    }

    /**
     * @return array
     */
    public function headings(): array
    {
        switch ($this->reportType) {
            case 'sales':
                return [
                    'Product Name',
                    'Quantity Sold',
                    'Unit Price (₱)',
                    'Total Amount (₱)'
                ];
            case 'inventory':
                return [
                    'ID',
                    'Product Name',
                    'Generic Name',
                    'Category',
                    'Quantity',
                    'Unit Price (₱)',
                    'Total Value (₱)',
                    'Expiry Date',
                    'Batch Number'
                ];
            case 'products':
                return [
                    'ID',
                    'Product Name',
                    'Generic Name',
                    'Category',
                    'Stock Quantity',
                    'Unit Price (₱)',
                    'Total Sold',
                    'Total Revenue (₱)'
                ];
            default:
                return [];
        }
    }

    /**
     * @param mixed $row
     * @return array
     */
    public function map($row): array
    {
        switch ($this->reportType) {
            case 'sales':
                return [
                    $row['product_name'],
                    $row['quantity_sold'],
                    $row['unit_price'],
                    $row['total_amount']
                ];
            case 'inventory':
                return [
                    $row['id'],
                    $row['name'],
                    $row['generic_name'],
                    $row['category'],
                    $row['quantity'],
                    $row['unit_price'],
                    $row['total_value'],
                    $row['expiry_date'],
                    $row['batch_number']
                ];
            case 'products':
                return [
                    $row['id'],
                    $row['name'],
                    $row['generic_name'],
                    $row['category'],
                    $row['stock_quantity'],
                    $row['unit_price'],
                    $row['total_sold'],
                    $row['total_revenue']
                ];
            default:
                return [];
        }
    }

    /**
     * @return string
     */
    public function title(): string
    {
        return ucfirst($this->reportType) . ' Report';
    }

    /**
     * @return string
     */
    public function startCell(): string
    {
        return 'A4';
    }

    /**
     * @param Worksheet $sheet
     * @return array
     */
    public function styles(Worksheet $sheet)
    {
        // Add report header
        $sheet->setCellValue('A1', 'MJ\'s Pharmacy - ' . ucfirst($this->reportType) . ' Report');
        $sheet->setCellValue('A2', 'Period: ' . $this->startDate->format('F j, Y') . ' to ' . $this->endDate->format('F j, Y'));
        $sheet->setCellValue('A3', 'Generated on: ' . Carbon::now()->format('F j, Y g:i A'));

        // Style the header
        $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(16);
        $sheet->getStyle('A2')->getFont()->setBold(true)->setSize(12);
        $sheet->getStyle('A3')->getFont()->setSize(10);

        // Get the data range
        $dataCount = $this->collection()->count();
        $headingCount = count($this->headings());
        $lastColumn = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($headingCount);
        $lastRow = 4 + $dataCount;

        // Style the data table
        return [
            // Header row styling
            'A4:' . $lastColumn . '4' => [
                'font' => ['bold' => true],
                'fill' => [
                    'fillType' => Fill::FILL_SOLID,
                    'color' => ['argb' => 'FFE0E0E0']
                ],
                'borders' => [
                    'allBorders' => [
                        'borderStyle' => Border::BORDER_THIN,
                    ],
                ],
            ],
            // Data rows styling
            'A5:' . $lastColumn . $lastRow => [
                'borders' => [
                    'allBorders' => [
                        'borderStyle' => Border::BORDER_THIN,
                    ],
                ],
            ],
        ];
    }
}