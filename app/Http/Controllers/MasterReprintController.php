<?php

namespace App\Http\Controllers;

use App\Models\MasterRequest;
use Illuminate\View\View;

class MasterReprintController extends Controller
{
    public function index(MasterRequest $master_request): View
    {
        $mr = $master_request->load([
            'line',
            'shift',
            'printBatches' => fn ($query) => $query
                ->with(['printedBy', 'items.folio'])
                ->orderByDesc('printed_at')
                ->orderByDesc('id'),
        ]);

        return view('master_reprints.index', compact('mr'));
    }
}
