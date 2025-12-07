<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Session;

class LocaleController extends Controller
{
    /**
     * Switch the application locale.
     */
    public function switch(Request $request, $locale)
    {
        // Validate locale
        if (!array_key_exists($locale, config('app.available_locales'))) {
            return redirect()->back();
        }
        
        // Store locale in session
        Session::put('locale', $locale);
        
        return redirect()->back();
    }
}
