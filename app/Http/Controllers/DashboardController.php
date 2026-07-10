<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\View\View;

class DashboardController extends Controller
{
    /**
     * Render the main dashboard interface.
     */
    public function index(Request $request): View
    {
        return view('dashboard');
    }
}
