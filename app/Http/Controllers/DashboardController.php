<?php

namespace App\Http\Controllers;

use Illuminate\View\View;

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
