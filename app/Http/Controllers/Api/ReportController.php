<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Exports\ConsumedMaterialsExport;
use App\Exports\DepartmentConsumptionExport;
use Maatwebsite\Excel\Facades\Excel;

class ReportController extends Controller
{
    public function consumedMaterials(Request $request)
    {
        $startDate = $request->input('start_date', now()->startOfMonth()->format('Y-m-d'));
        $endDate = $request->input('end_date', now()->endOfMonth()->format('Y-m-d'));
        
        $filename = 'Inventaire_des_matieres_consommees_' . 
                    str_replace('-', '_', $startDate) . '_to_' . 
                    str_replace('-', '_', $endDate) . '.xlsx';
        
        return Excel::download(new ConsumedMaterialsExport($startDate, $endDate), $filename);
    }
    
    public function departmentConsumption(Request $request)
    {
        $startDate = $request->input('start_date', now()->startOfYear()->format('Y-m-d'));
        $endDate = $request->input('end_date', now()->endOfYear()->format('Y-m-d'));
        $itemIds = $request->input('item_ids'); // Array of item IDs, or null for all
        
        $filename = 'Rapport_Consommation_Departements_' . 
                    str_replace('-', '_', $startDate) . '_to_' . 
                    str_replace('-', '_', $endDate) . '.xlsx';
        
        return Excel::download(new DepartmentConsumptionExport($startDate, $endDate, $itemIds), $filename);
    }
}
