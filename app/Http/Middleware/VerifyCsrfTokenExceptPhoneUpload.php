<?php

namespace App\Http\Middleware;

use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken as Middleware;

class VerifyCsrfTokenExceptPhoneUpload extends Middleware
{
    protected $except = [
        'phone-upload',
        'phone-upload/*',
        'phone-uploads/*',
    ];
}
