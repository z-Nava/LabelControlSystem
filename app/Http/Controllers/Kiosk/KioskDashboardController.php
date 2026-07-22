<?php

namespace App\Http\Controllers\Kiosk;

use App\Http\Controllers\Controller;
use Illuminate\View\View;

class KioskDashboardController extends Controller
{
    public function __invoke(): View
    {
        return view('kiosk.dashboard');
    }
}
