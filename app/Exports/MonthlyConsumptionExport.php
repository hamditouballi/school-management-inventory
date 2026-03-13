<?php

namespace App\Exports;

use App\Models\BonDeSortie;
use App\Models\Category;
use Carbon\Carbon;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;
use Maatwebsite\Excel\Concerns\WithTitle;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class MonthlyConsumptionExport implements WithEvents, WithMultipleSheets
{
    protected $startDate;

    protected $endDate;

    protected $dateRange;

    protected $sheets = [];

    public function __construct($startDate = null, $endDate = null)
    {
        $this->startDate = $startDate ? Carbon::parse($startDate) : now()->startOfMonth();
        $this->endDate = $endDate ? Carbon::parse($endDate) : now()->endOfMonth();
        $this->dateRange = $this->startDate->format('d/m/Y').' - '.$this->endDate->format('d/m/Y');
        $this->prepareSheets();
    }

    public function sheets(): array
    {
        return $this->sheets;
    }

    protected function prepareSheets()
    {
        // Get all consumed items from Bon de Sortie within date range
        $bonDeSorties = BonDeSortie::with('item.category')
            ->whereBetween('date', [$this->startDate, $this->endDate])
            ->get()
            ->groupBy('item_id');

        $allItems = collect();

        foreach ($bonDeSorties as $itemId => $sorties) {
            $firstSortie = $sorties->first();
            $item = $firstSortie->item;

            if (! $item) {
                continue;
            }

            $totalQuantity = $sorties->sum('quantity');
            $unitPrice = $item->price;
            $totalPrice = $totalQuantity * $unitPrice;

            $allItems->push([
                'designation' => $item->designation,
                'quantity' => $totalQuantity,
                'unit_price' => $unitPrice,
                'total_price' => $totalPrice,
                'category_id' => $item->category_id,
                'category_name' => $item->category?->name ?? 'AUTRES',
            ]);
        }

        // Sheet 1: CONSOMMATION MENSUELLE (all items)
        $this->sheets[] = new MonthlyConsumptionSheet(
            'CONSOMMATION MENSUELLE',
            $allItems,
            ['DESIGNATION', 'QUANTITÉ CONSOMMÉE', 'PRIX UNITAIRE', 'PRIX TOTAL'],
            $this->dateRange,
            false
        );

        // Sheet 2: MARQUEURS
        $marqueursItems = $allItems->filter(fn ($item) => $item['category_name'] === 'MARQUEURS');
        $this->sheets[] = new MonthlyConsumptionSheet(
            'MARQUEURS',
            $marqueursItems,
            ['DESIGNATION', 'QUANTITÉ', 'PRIX UNITAIRE', 'PRIX TOTAL', 'OBSERVATIONS'],
            $this->dateRange,
            true
        );

        // Sheet 3: TONNER
        $tonnerItems = $allItems->filter(fn ($item) => $item['category_name'] === 'TONNER');
        $this->sheets[] = new MonthlyConsumptionSheet(
            'TONNER',
            $tonnerItems,
            ['DESIGNATION', 'QUANTITÉ', 'PRIX UNITAIRE', 'PRIX TOTAL', 'OBSERVATIONS'],
            $this->dateRange,
            true
        );

        // Sheet 4: CONSOMMATION TOTALE (summary)
        $categories = Category::all();
        $summaryData = collect();
        $summaryTotal = 0;

        foreach ($categories as $category) {
            $categoryItems = $allItems->filter(fn ($item) => $item['category_name'] === $category->name);
            if ($categoryItems->count() > 0) {
                $categoryQty = $categoryItems->sum('quantity');
                $categoryTotal = $categoryItems->sum('total_price');
                $summaryTotal += $categoryTotal;
                $summaryData->push([
                    'nature' => $category->name,
                    'quantity' => $categoryQty,
                    'total' => $categoryTotal,
                ]);
            }
        }

        // Add others
        $otherItems = $allItems->filter(fn ($item) => ! $categories->contains('name', $item['category_name']));
        if ($otherItems->count() > 0) {
            $otherQty = $otherItems->sum('quantity');
            $otherTotal = $otherItems->sum('total_price');
            $summaryTotal += $otherTotal;
            $summaryData->push([
                'nature' => 'AUTRES',
                'quantity' => $otherQty,
                'total' => $otherTotal,
            ]);
        }

        $this->sheets[] = new MonthlyConsumptionSummarySheet(
            'CONSOMMATION TOTALE',
            $summaryData,
            $this->dateRange
        );
    }

    public function registerEvents(): array
    {
        return [];
    }
}

class MonthlyConsumptionSheet implements \Maatwebsite\Excel\Concerns\FromArray, \Maatwebsite\Excel\Concerns\WithStyles, WithTitle
{
    private $title;

    private $data;

    private $headers;

    private $dateRange;

    private $hasObservations;

    public function __construct($title, $items, $headers, $dateRange, $hasObservations)
    {
        $this->title = $title;
        $this->headers = $headers;
        $this->dateRange = $dateRange;
        $this->hasObservations = $hasObservations;

        $data = collect();
        $data->push(['Inventaire des matières consommées']);
        $data->push(['Période: '.$dateRange]);
        $data->push([]); // Empty row
        $data->push($headers);

        $grandTotal = 0;
        foreach ($items as $item) {
            $row = [
                $item['designation'],
                number_format($item['quantity'], 2),
                number_format($item['unit_price'], 2).' DH',
                number_format($item['total_price'], 2).' DH',
            ];
            if ($hasObservations) {
                $row[] = '';
            }
            $data->push($row);
            $grandTotal += $item['total_price'];
        }

        $data->push([]); // Empty row
        $totalRow = ['', '', 'TOTAL', number_format($grandTotal, 2).' DH'];
        if ($hasObservations) {
            $totalRow[] = '';
        }
        $data->push($totalRow);

        $this->data = $data->toArray();
    }

    public function title(): string
    {
        return $this->title;
    }

    public function array(): array
    {
        return $this->data;
    }

    public function styles(Worksheet $sheet)
    {
        $highestRow = $sheet->getHighestRow();
        $lastColumn = $this->hasObservations ? 'E' : 'D';

        // Style title (row 1)
        $sheet->mergeCells("A1:{$lastColumn}1");
        $sheet->getStyle("A1:{$lastColumn}1")->applyFromArray([
            'font' => [
                'bold' => true,
                'size' => 16,
                'color' => ['rgb' => 'FFFFFF'],
            ],
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => ['rgb' => '2E75B6'],
            ],
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_CENTER,
                'vertical' => Alignment::VERTICAL_CENTER,
            ],
        ]);

        // Style date period (row 2)
        $sheet->mergeCells("A2:{$lastColumn}2");
        $sheet->getStyle("A2:{$lastColumn}2")->applyFromArray([
            'font' => [
                'bold' => true,
                'size' => 12,
            ],
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => ['rgb' => 'D9E1F2'],
            ],
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_CENTER,
                'vertical' => Alignment::VERTICAL_CENTER,
            ],
        ]);

        // Style headers (row 4)
        $sheet->getStyle("A4:{$lastColumn}4")->applyFromArray([
            'font' => [
                'bold' => true,
                'color' => ['rgb' => 'FFFFFF'],
            ],
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => ['rgb' => '2E75B6'],
            ],
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_CENTER,
                'vertical' => Alignment::VERTICAL_CENTER,
            ],
            'borders' => [
                'allBorders' => [
                    'borderStyle' => Border::BORDER_THIN,
                    'color' => ['rgb' => '000000'],
                ],
            ],
        ]);

        // Style data rows with alternating colors
        for ($row = 5; $row < $highestRow - 1; $row++) {
            if ($row % 2 == 0) {
                $sheet->getStyle("A{$row}:{$lastColumn}{$row}")->applyFromArray([
                    'fill' => [
                        'fillType' => Fill::FILL_SOLID,
                        'startColor' => ['rgb' => 'F2F2F2'],
                    ],
                    'borders' => [
                        'allBorders' => [
                            'borderStyle' => Border::BORDER_THIN,
                            'color' => ['rgb' => '000000'],
                        ],
                    ],
                ]);
            } else {
                $sheet->getStyle("A{$row}:{$lastColumn}{$row}")->applyFromArray([
                    'borders' => [
                        'allBorders' => [
                            'borderStyle' => Border::BORDER_THIN,
                            'color' => ['rgb' => '000000'],
                        ],
                    ],
                ]);
            }
        }

        // Style total row (last row)
        $sheet->getStyle("A{$highestRow}:{$lastColumn}{$highestRow}")->applyFromArray([
            'font' => [
                'bold' => true,
                'size' => 12,
                'color' => ['rgb' => 'FFFFFF'],
            ],
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => ['rgb' => '70AD47'],
            ],
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_CENTER,
            ],
            'borders' => [
                'allBorders' => [
                    'borderStyle' => Border::BORDER_THIN,
                    'color' => ['rgb' => '000000'],
                ],
            ],
        ]);

        // Set column widths
        if ($this->hasObservations) {
            $sheet->getColumnDimension('A')->setWidth(35);
            $sheet->getColumnDimension('B')->setWidth(18);
            $sheet->getColumnDimension('C')->setWidth(18);
            $sheet->getColumnDimension('D')->setWidth(18);
            $sheet->getColumnDimension('E')->setWidth(20);
        } else {
            $sheet->getColumnDimension('A')->setWidth(35);
            $sheet->getColumnDimension('B')->setWidth(22);
            $sheet->getColumnDimension('C')->setWidth(20);
            $sheet->getColumnDimension('D')->setWidth(20);
        }

        // Center align data columns
        $sheet->getStyle("B5:{$lastColumn}".($highestRow - 1))->applyFromArray([
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_CENTER,
            ],
        ]);

        // Set row heights
        $sheet->getRowDimension(1)->setRowHeight(30);
        $sheet->getRowDimension(2)->setRowHeight(25);
    }
}

class MonthlyConsumptionSummarySheet implements \Maatwebsite\Excel\Concerns\FromArray, \Maatwebsite\Excel\Concerns\WithStyles, WithTitle
{
    private $title;

    private $data;

    private $dateRange;

    public function __construct($title, $summaryData, $dateRange)
    {
        $this->title = $title;
        $this->dateRange = $dateRange;

        $data = collect();
        $data->push(['Inventaire des matières consommées']);
        $data->push(['Période: '.$dateRange]);
        $data->push([]); // Empty row
        $data->push(['NATURE', 'QUANTITÉ TOTALE', 'PRIX TOTAL']);

        $grandTotal = 0;
        foreach ($summaryData as $item) {
            $data->push([
                $item['nature'],
                number_format($item['quantity'], 2),
                number_format($item['total'], 2).' DH',
            ]);
            $grandTotal += $item['total'];
        }

        $data->push([]); // Empty row
        $data->push(['', 'TOTAL GÉNÉRAL', number_format($grandTotal, 2).' DH']);

        $this->data = $data->toArray();
    }

    public function title(): string
    {
        return $this->title;
    }

    public function array(): array
    {
        return $this->data;
    }

    public function styles(Worksheet $sheet)
    {
        $highestRow = $sheet->getHighestRow();
        $lastColumn = 'C';

        // Style title (row 1)
        $sheet->mergeCells('A1:C1');
        $sheet->getStyle('A1:C1')->applyFromArray([
            'font' => [
                'bold' => true,
                'size' => 16,
                'color' => ['rgb' => 'FFFFFF'],
            ],
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => ['rgb' => '2E75B6'],
            ],
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_CENTER,
                'vertical' => Alignment::VERTICAL_CENTER,
            ],
        ]);

        // Style date period (row 2)
        $sheet->mergeCells('A2:C2');
        $sheet->getStyle('A2:C2')->applyFromArray([
            'font' => [
                'bold' => true,
                'size' => 12,
            ],
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => ['rgb' => 'D9E1F2'],
            ],
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_CENTER,
                'vertical' => Alignment::VERTICAL_CENTER,
            ],
        ]);

        // Style headers (row 4)
        $sheet->getStyle('A4:C4')->applyFromArray([
            'font' => [
                'bold' => true,
                'color' => ['rgb' => 'FFFFFF'],
            ],
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => ['rgb' => '2E75B6'],
            ],
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_CENTER,
                'vertical' => Alignment::VERTICAL_CENTER,
            ],
            'borders' => [
                'allBorders' => [
                    'borderStyle' => Border::BORDER_THIN,
                    'color' => ['rgb' => '000000'],
                ],
            ],
        ]);

        // Style data rows with alternating colors
        for ($row = 5; $row < $highestRow - 1; $row++) {
            if ($row % 2 == 0) {
                $sheet->getStyle("A{$row}:C{$row}")->applyFromArray([
                    'fill' => [
                        'fillType' => Fill::FILL_SOLID,
                        'startColor' => ['rgb' => 'F2F2F2'],
                    ],
                    'borders' => [
                        'allBorders' => [
                            'borderStyle' => Border::BORDER_THIN,
                            'color' => ['rgb' => '000000'],
                        ],
                    ],
                ]);
            } else {
                $sheet->getStyle("A{$row}:C{$row}")->applyFromArray([
                    'borders' => [
                        'allBorders' => [
                            'borderStyle' => Border::BORDER_THIN,
                            'color' => ['rgb' => '000000'],
                        ],
                    ],
                ]);
            }
        }

        // Style total row (last row)
        $sheet->getStyle("A{$highestRow}:C{$highestRow}")->applyFromArray([
            'font' => [
                'bold' => true,
                'size' => 12,
                'color' => ['rgb' => 'FFFFFF'],
            ],
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => ['rgb' => '70AD47'],
            ],
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_CENTER,
            ],
            'borders' => [
                'allBorders' => [
                    'borderStyle' => Border::BORDER_THIN,
                    'color' => ['rgb' => '000000'],
                ],
            ],
        ]);

        // Set column widths
        $sheet->getColumnDimension('A')->setWidth(30);
        $sheet->getColumnDimension('B')->setWidth(25);
        $sheet->getColumnDimension('C')->setWidth(25);

        // Center align data columns
        $sheet->getStyle('B5:C'.($highestRow - 1))->applyFromArray([
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_CENTER,
            ],
        ]);

        // Set row heights
        $sheet->getRowDimension(1)->setRowHeight(30);
        $sheet->getRowDimension(2)->setRowHeight(25);
    }
}
