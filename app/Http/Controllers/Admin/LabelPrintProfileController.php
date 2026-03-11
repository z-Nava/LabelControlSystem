<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\TestLabelPrinterRequest;
use App\Http\Requests\Admin\StoreLabelPrintProfileRequest;
use App\Http\Requests\Admin\UpdateLabelPrintProfileRequest;
use App\Models\LabelPrintProfile;
use App\Models\LabelSku;
use App\Models\LabelTemplate;
use App\Services\Catalogs\LabelPrintProfileService;
use App\Services\Printing\RawPrinterService;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class LabelPrintProfileController extends Controller
{
    public function __construct(
        private readonly LabelPrintProfileService $service,
        private readonly RawPrinterService $rawPrinterService,
    ) {}

    public function index(): View
    {
        $search = request('q');
        $profiles = $this->service->paginate(15, $search);

        return view('label_print_profiles.index', compact('profiles', 'search'));
    }

    public function create(): View
    {
        $labelSkus = LabelSku::query()->active()->orderBy('sku')->get();
        $templates = LabelTemplate::query()->active()->orderBy('name')->get();

        return view('label_print_profiles.create', compact('labelSkus', 'templates'));
    }

    public function store(StoreLabelPrintProfileRequest $request): RedirectResponse
    {
        $this->service->create($request->validated(), auth()->id());

        return redirect()->route('label_print_profiles.index')->with('success', 'Perfil de impresión creado correctamente.');
    }

    public function testPrint(TestLabelPrinterRequest $request): RedirectResponse
    {
        $data = $request->validated();
        $response = $this->rawPrinterService->sendTestLabel(
            ip: (string) $data['default_printer_ip'],
            printerName: $data['default_printer_name'] ?? null,
            dpi: (int) ($data['dpi'] ?? 203),
        );

        if (!$response['ok']) {
            return back()->withInput()->with('error', $response['message']);
        }

        return back()->withInput()->with('success', $response['message']);
    }

    public function edit(LabelPrintProfile $label_print_profile): View
    {
        $labelSkus = LabelSku::query()->active()->orderBy('sku')->get();
        $templates = LabelTemplate::query()->active()->orderBy('name')->get();

        return view('label_print_profiles.edit', [
            'profile' => $label_print_profile,
            'labelSkus' => $labelSkus,
            'templates' => $templates,
        ]);
    }

    public function update(UpdateLabelPrintProfileRequest $request, LabelPrintProfile $label_print_profile): RedirectResponse
    {
        $this->service->update($label_print_profile, $request->validated(), auth()->id());

        return redirect()->route('label_print_profiles.index')->with('success', 'Perfil de impresión actualizado correctamente.');
    }

    public function toggle(LabelPrintProfile $label_print_profile): RedirectResponse
    {
        $this->service->toggleActive($label_print_profile, auth()->id());

        return redirect()->route('label_print_profiles.index')->with('success', 'Estado del perfil actualizado.');
    }
}
