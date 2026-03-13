<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;

class DashboardController extends Controller
{
    public function index()
    {
        // Prevent directors from accessing dashboard
        if (auth()->user()->role === 'director') {
            return redirect()->route('requests.page')
                ->with('error', 'You do not have access to the dashboard.');
        }

        return view('dashboard');
    }
}
