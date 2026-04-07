<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreSkuSerialFormatRequest;
use App\Http\Requests\Admin\UpdateSkuSerialFormatRequest;
use App\Models\LabelSku;
use App\Models\SkuSerialFormat;
use App\Services\Catalogs\SkuSerialFormatService;
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
        $formats = $this->service->paginate(15, $search);

        return view('sku_serial_formats.index', compact('formats', 'search'));
    }

    public function create(): View
    {
        $activeSkus = LabelSku::query()
            ->active()
            ->orderBy('sku')
            ->orderBy('serial_standard')
            ->get(['sku', 'serial_standard', 'label_part_number']);

        return view('sku_serial_formats.create', compact('activeSkus'));
    }

    public function store(StoreSkuSerialFormatRequest $request): RedirectResponse
    {
        $this->service->create($request->validated(), auth()->id());

        return redirect()->route('sku_serial_formats.index')
            ->with('success', 'Formato serial agregado correctamente.');
    }

    public function edit(SkuSerialFormat $sku_serial_format): View
    {
        $activeSkus = LabelSku::query()
            ->where(function ($query) use ($sku_serial_format) {
                $query->active()
                    ->orWhere('sku', $sku_serial_format->sku);
            })
            ->orderBy('sku')
            ->orderBy('serial_standard')
            ->get(['sku', 'serial_standard', 'label_part_number']);

        return view('sku_serial_formats.edit', [
            'format' => $sku_serial_format,
            'activeSkus' => $activeSkus,
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
