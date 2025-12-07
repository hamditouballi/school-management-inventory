<?php

namespace App\Exports;

use App\Models\BonDeSortie;
use App\Models\Department;
use App\Models\Item;
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

class DepartmentConsumptionExport implements FromCollection, WithHeadings, WithStyles, WithTitle, WithEvents
{
    protected $startDate;
    protected $endDate;
    protected $dateRange;
    protected $itemIds;
    protected $departments;

    public function __construct($startDate = null, $endDate = null, $itemIds = null)
    {
        $this->startDate = $startDate ? Carbon::parse($startDate) : now()->startOfYear();
        $this->endDate = $endDate ? Carbon::parse($endDate) : now()->endOfYear();
        $this->dateRange = $this->startDate->format('d/m/Y') . ' - ' . $this->endDate->format('d/m/Y');
        $this->itemIds = $itemIds; // If null, include all items
    }

    public function collection()
    {
        $data = collect();
        
        // Add title row
        $data->push(['Rapport de Consommation par Département']);
        $data->push(['Période: ' . $this->dateRange]);
        $data->push([]); // Empty row
        
        // Table header
        $data->push(['SERVICE DU DIRECTION', 'TOTAL annuel', 'P,U (DH)', 'CONSOMMATION TOTALE (DH)']);
        
        // Get all departments
        $this->departments = Department::all();
        
        // Get items based on selection
        $itemsQuery = Item::query();
        if ($this->itemIds && count($this->itemIds) > 0) {
            $itemsQuery->whereIn('id', $this->itemIds);
        }
        $items = $itemsQuery->get();
        
        $grandTotalQuantity = 0;
        $grandTotalCost = 0;
        
        // For each department
        foreach ($this->departments as $department) {
            // Get all consumption for this department
            $departmentBonDeSorties = BonDeSortie::whereHas('request', function($query) use ($department) {
                $query->whereHas('user', function($q) use ($department) {
                    $q->where('department_id', $department->id);
                });
            })
            ->whereBetween('date', [$this->startDate, $this->endDate]);
            
            // Filter by selected items if specified
            if ($this->itemIds && count($this->itemIds) > 0) {
                $departmentBonDeSorties->whereIn('item_id', $this->itemIds);
            }
            
            $departmentBonDeSorties = $departmentBonDeSorties->with('item')->get();
            
            // Calculate totals
            $totalQuantity = $departmentBonDeSorties->sum('quantity');
            $totalCost = $departmentBonDeSorties->sum(function($bon) {
                return $bon->quantity * ($bon->item ? $bon->item->price : 0);
            });
            
            // Calculate weighted average unit price
            $avgUnitPrice = $totalQuantity > 0 ? $totalCost / $totalQuantity : 0;
            
            $data->push([
                $department->name,
                number_format($totalQuantity, 0),
                number_format($avgUnitPrice, 2),
                number_format($totalCost, 2)
            ]);
            
            $grandTotalQuantity += $totalQuantity;
            $grandTotalCost += $totalCost;
        }
        
        // Calculate overall average unit price
        $overallAvgPrice = $grandTotalQuantity > 0 ? $grandTotalCost / $grandTotalQuantity : 0;
        
        // Add grand total row
        $data->push([
            'TOTAL annuel',
            number_format($grandTotalQuantity, 0),
            number_format($overallAvgPrice, 2),
            number_format($grandTotalCost, 2)
        ]);
        
        return $data;
    }

    public function headings(): array
    {
        return [];
    }

    public function title(): string
    {
        return 'Consommation Départements';
    }

    public function styles(Worksheet $sheet)
    {
        return [
            1 => [
                'font' => ['bold' => true, 'size' => 16],
                'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
            ],
            2 => [
                'font' => ['bold' => true, 'size' => 12],
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
                $highestColumn = $sheet->getHighestDataColumn();
                
                // Merge cells for title and date
                $sheet->mergeCells("A1:{$highestColumn}1");
                $sheet->mergeCells("A2:{$highestColumn}2");
                
                // Set column widths
                $sheet->getColumnDimension('A')->setWidth(30); // Department name
                $sheet->getColumnDimension('B')->setWidth(20); // Total quantity
                $sheet->getColumnDimension('C')->setWidth(15); // Unit price
                $sheet->getColumnDimension('D')->setWidth(25); // Total consumption
                
                // Style title
                $sheet->getStyle("A1:{$highestColumn}1")->applyFromArray([
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
                $sheet->getStyle("A2:{$highestColumn}2")->applyFromArray([
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
                $sheet->getStyle("A4:{$highestColumn}4")->applyFromArray([
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
                        $sheet->getStyle("A{$row}:{$highestColumn}{$row}")->applyFromArray([
                            'fill' => [
                                'fillType' => Fill::FILL_SOLID,
                                'startColor' => ['rgb' => 'F2F2F2']
                            ]
                        ]);
                    }
                }
                
                // Style total row (last row)
                $sheet->getStyle("A{$highestRow}:{$highestColumn}{$highestRow}")->applyFromArray([
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
                $sheet->getStyle("A1:{$highestColumn}{$highestRow}")->applyFromArray([
                    'borders' => [
                        'allBorders' => [
                            'borderStyle' => Border::BORDER_THIN,
                            'color' => ['rgb' => '000000']
                        ]
                    ]
                ]);
                
                // Center align all data cells
                $sheet->getStyle("A4:{$highestColumn}{$highestRow}")->applyFromArray([
                    'alignment' => [
                        'horizontal' => Alignment::HORIZONTAL_CENTER,
                        'vertical' => Alignment::VERTICAL_CENTER
                    ]
                ]);
                
                // Left align department names
                $sheet->getStyle("A5:A{$highestRow}")->applyFromArray([
                    'alignment' => [
                        'horizontal' => Alignment::HORIZONTAL_LEFT
                    ]
                ]);
                
                // Set row heights
                $sheet->getRowDimension(1)->setRowHeight(30);
                $sheet->getRowDimension(2)->setRowHeight(25);
            }
        ];
    }
}
