<?php

namespace App\Exports;

use App\Models\BonDeSortie;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Concerns\WithEvents;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Font;
use Maatwebsite\Excel\Events\AfterSheet;
use Carbon\Carbon;

class ConsumedMaterialsExport implements FromCollection, WithHeadings, WithStyles, WithTitle, WithEvents
{
    protected $startDate;
    protected $endDate;
    protected $dateRange;

    public function __construct($startDate = null, $endDate = null)
    {
        $this->startDate = $startDate ? Carbon::parse($startDate) : now()->startOfMonth();
        $this->endDate = $endDate ? Carbon::parse($endDate) : now()->endOfMonth();
        $this->dateRange = $this->startDate->format('d/m/Y') . ' - ' . $this->endDate->format('d/m/Y');
    }

    public function collection()
    {
        $data = collect();
        
        // Add title row
        $data->push(['Inventaire des matières consommées']);
        $data->push(['Période: ' . $this->dateRange]);
        $data->push([]); // Empty row
        
        // Add table headers
        $data->push(['DESIGNATION', 'QUANTITÉ CONSOMMÉE', 'PRIX UNITAIRE', 'PRIX TOTAL']);
        
        // Get all consumed items from Bon de Sortie within date range
        $bonDeSorties = BonDeSortie::with('item')
            ->whereBetween('date', [$this->startDate, $this->endDate])
            ->get()
            ->groupBy('item_id');
        
        $grandTotal = 0;
        
        foreach ($bonDeSorties as $itemId => $sorties) {
            $firstSortie = $sorties->first();
            $item = $firstSortie->item;
            
            if (!$item) continue;
            
            // Sum quantities for this item
            $totalQuantity = $sorties->sum('quantity');
            $unitPrice = $item->price;
            $totalPrice = $totalQuantity * $unitPrice;
            $grandTotal += $totalPrice;
            
            $data->push([
                $item->designation,
                number_format($totalQuantity, 2),
                number_format($unitPrice, 2) . ' DH',
                number_format($totalPrice, 2) . ' DH'
            ]);
        }
        
        // Add empty row before total
        $data->push([]);
        
        // Add grand total
        $data->push(['TOTAL GÉNÉRAL', '', '', number_format($grandTotal, 2) . ' DH']);
        
        return $data;
    }

    public function headings(): array
    {
        return [];
    }

    public function title(): string
    {
        return 'Inventaire Consommées';
    }

    public function styles(Worksheet $sheet)
    {
        return [
            1 => [
                'font' => ['bold' => true, 'size' => 16],
                'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
            ],
            2 => [
                'font' => ['bold' => true, 'size' => 14],
                'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
            ],
        ];
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function(AfterSheet $event) {
                $sheet = $event->sheet->getDelegate();
                $highestRow = $sheet->getHighestRow();
                
                // Merge cells for title and date
                $sheet->mergeCells('A1:D1');
                $sheet->mergeCells('A2:D2');
                
                // Set column widths
                $sheet->getColumnDimension('A')->setWidth(35);
                $sheet->getColumnDimension('B')->setWidth(20);
                $sheet->getColumnDimension('C')->setWidth(18);
                $sheet->getColumnDimension('D')->setWidth(18);
                
                // Style title
                $sheet->getStyle('A1:D1')->applyFromArray([
                    'font' => [
                        'bold' => true,
                        'size' => 16,
                        'color' => ['rgb' => 'FFFFFF']
                    ],
                    'fill' => [
                        'fillType' => Fill::FILL_SOLID,
                        'startColor' => ['rgb' => '4472C4']
                    ],
                    'alignment' => [
                        'horizontal' => Alignment::HORIZONTAL_CENTER,
                        'vertical' => Alignment::VERTICAL_CENTER
                    ]
                ]);
                
                // Style date period
                $sheet->getStyle('A2:D2')->applyFromArray([
                    'font' => [
                        'bold' => true,
                        'size' => 12
                    ],
                    'fill' => [
                        'fillType' => Fill::FILL_SOLID,
                        'startColor' => ['rgb' => 'D9E1F2']
                    ],
                    'alignment' => [
                        'horizontal' => Alignment::HORIZONTAL_CENTER,
                        'vertical' => Alignment::VERTICAL_CENTER
                    ]
                ]);
                
                // Style table headers (row 4)
                $sheet->getStyle('A4:D4')->applyFromArray([
                    'font' => [
                        'bold' => true,
                        'color' => ['rgb' => 'FFFFFF']
                    ],
                    'fill' => [
                        'fillType' => Fill::FILL_SOLID,
                        'startColor' => ['rgb' => '4472C4']
                    ],
                    'alignment' => [
                        'horizontal' => Alignment::HORIZONTAL_CENTER,
                        'vertical' => Alignment::VERTICAL_CENTER
                    ]
                ]);
                
                // Style data rows with alternating colors
                for ($row = 5; $row < $highestRow - 1; $row++) {
                    if ($row % 2 == 0) {
                        $sheet->getStyle("A{$row}:D{$row}")->applyFromArray([
                            'fill' => [
                                'fillType' => Fill::FILL_SOLID,
                                'startColor' => ['rgb' => 'F2F2F2']
                            ]
                        ]);
                    }
                }
                
                // Style total row (last row)
                $sheet->getStyle("A{$highestRow}:D{$highestRow}")->applyFromArray([
                    'font' => [
                        'bold' => true,
                        'size' => 12,
                        'color' => ['rgb' => 'FFFFFF']
                    ],
                    'fill' => [
                        'fillType' => Fill::FILL_SOLID,
                        'startColor' => ['rgb' => '70AD47']
                    ],
                    'alignment' => [
                        'horizontal' => Alignment::HORIZONTAL_CENTER
                    ]
                ]);
                
                // Apply borders to all cells
                $sheet->getStyle("A1:D{$highestRow}")->applyFromArray([
                    'borders' => [
                        'allBorders' => [
                            'borderStyle' => Border::BORDER_THIN,
                            'color' => ['rgb' => '000000']
                        ]
                    ]
                ]);
                
                // Center align quantity and price columns
                $sheet->getStyle("B5:D{$highestRow}")->applyFromArray([
                    'alignment' => [
                        'horizontal' => Alignment::HORIZONTAL_CENTER
                    ]
                ]);
                
                // Set row height for title
                $sheet->getRowDimension(1)->setRowHeight(30);
                $sheet->getRowDimension(2)->setRowHeight(25);
            }
        ];
    }
}
