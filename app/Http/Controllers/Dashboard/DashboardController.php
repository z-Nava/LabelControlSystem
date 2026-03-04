<?php

namespace App\Http\Controllers\Dashboard;

use Illuminate\View\View;
use App\Http\Controllers\Controller;

class DashboardController extends Controller
{
    public function __invoke(): View
    {
        $user = auth()->user();

        if ($user->hasRole('admin')) {
            return view('dashboards.admin');
        }

        return view('dashboards.label_room');
    }
}
