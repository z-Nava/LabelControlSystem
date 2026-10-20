<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\UpdateKioskOptionRequest;
use App\Services\Kiosk\KioskOptionService;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class KioskOptionController extends Controller
{
    public function index(KioskOptionService $options): View
    {
        return view('admin.kiosk_options.index', [
            'kioskOptions' => KioskOptionService::OPTIONS,
            'kioskStatuses' => KioskOptionService::STATUSES,
            'kioskOptionStatuses' => $options->all(),
        ]);
    }

    public function update(UpdateKioskOptionRequest $request, string $option, KioskOptionService $options): RedirectResponse
    {
        abort_unless(array_key_exists($option, KioskOptionService::OPTIONS), 404);

        $options->setStatus($option, $request->validated('status'));

        return redirect()->route('admin.kiosk_options.index')
            ->with('success', 'Estado de Kiosk actualizado para '.KioskOptionService::OPTIONS[$option].'.');
    }
}
