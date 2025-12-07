<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function index()
    {
        // Prevent teachers from accessing dashboard
        if (auth()->user()->role === 'teacher') {
            return redirect()->route('requests.index')
                ->with('error', 'You do not have access to the dashboard.');
        }
        
        return view('dashboard');
    }
}
