<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use App\Services\Dashboard\PendingRequestCountService;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __invoke(Request $request, PendingRequestCountService $countService): View
    {
        $user = $request->user();

        if ($user->hasRole('admin') && session('auth_access_mode') === 'admin') {
            return view('dashboards.admin');
        }

        return view('dashboards.label_room', [
            'pendingRequestCounts' => $countService->for($user),
        ]);
    }
}
