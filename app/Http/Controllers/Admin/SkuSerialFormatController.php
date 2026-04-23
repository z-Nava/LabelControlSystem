<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreSkuSerialFormatRequest;
use App\Http\Requests\Admin\UpdateSkuSerialFormatRequest;
use App\Models\LabelSku;
use App\Models\SkuSerialFormat;
use App\Services\Catalogs\SkuSerialFormatService;
use App\Support\SerialStandards;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class SkuSerialFormatController extends Controller
{
    public function __construct(private readonly SkuSerialFormatService $service)
    {
    }

    public function index(): View
    {
        $search = request('q');
        $formatsByStandard = collect(SerialStandards::all())
            ->mapWithKeys(fn (string $standard) => [$standard => $this->service->listByStandard($standard, $search)])
            ->all();

        return view('sku_serial_formats.index', compact('formatsByStandard', 'search'));
    }

    public function create(): View
    {
        $forcedStandard = SerialStandards::normalize((string) request('standard', SerialStandards::UL));

        $activeSkus = LabelSku::query()
            ->active()
            ->where('serial_standard', $forcedStandard)
            ->orderBy('sku')
            ->orderBy('serial_standard')
            ->get(['sku', 'serial_standard', 'label_part_number']);

        $viewByStandard = [
            SerialStandards::UL => 'sku_serial_formats.create_ul',
            SerialStandards::EMEA => 'sku_serial_formats.create_emea',
            SerialStandards::ANZ => 'sku_serial_formats.create_anz',
        ];

        return view($viewByStandard[$forcedStandard], compact('activeSkus', 'forcedStandard'));
    }

    public function store(StoreSkuSerialFormatRequest $request): RedirectResponse
    {
        $this->service->create($request->validated(), auth()->id());

        return redirect()->route('sku_serial_formats.index')
            ->with('success', 'Formato serial agregado correctamente.');
    }

    public function edit(SkuSerialFormat $sku_serial_format): View
    {
        $sku_serial_format->load(['ulConfig', 'emeaConfig', 'anzConfig']);

        $activeSkus = LabelSku::query()
            ->where(function ($query) use ($sku_serial_format) {
                $query->active()
                    ->orWhere('sku', $sku_serial_format->sku);
            })
            ->orderBy('sku')
            ->orderBy('serial_standard')
            ->get(['sku', 'serial_standard', 'label_part_number']);

        $serialStandard = SerialStandards::normalize((string) $sku_serial_format->serial_standard);
        $viewByStandard = [
            SerialStandards::UL => 'sku_serial_formats.create_ul',
            SerialStandards::EMEA => 'sku_serial_formats.create_emea',
            SerialStandards::ANZ => 'sku_serial_formats.create_anz',
        ];

        return view($viewByStandard[$serialStandard], [
            'format' => $sku_serial_format,
            'activeSkus' => $activeSkus,
            'forcedStandard' => $serialStandard,
            'isEdit' => true,
        ]);
    }

    public function update(UpdateSkuSerialFormatRequest $request, SkuSerialFormat $sku_serial_format): RedirectResponse
    {
        $this->service->update($sku_serial_format, $request->validated(), auth()->id());

        return redirect()->route('sku_serial_formats.index')
            ->with('success', 'Formato serial actualizado correctamente.');
    }

    public function toggle(SkuSerialFormat $sku_serial_format): RedirectResponse
    {
        $this->service->toggleActive($sku_serial_format, auth()->id());

        return redirect()->route('sku_serial_formats.index')
            ->with('success', 'Estado actualizado.');
    }
}
