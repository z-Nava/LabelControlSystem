<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreLabelSkuRequest;
use App\Http\Requests\Admin\UpdateLabelSkuRequest;
use App\Models\LabelSku;
use App\Services\Catalogs\LabelSkuService;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class LabelSkuController extends Controller
{
    public function __construct(private readonly LabelSkuService $service)
    {
    }

    public function index(): View
    {
        $search = request('q');
        $labelSkusByStandard = $this->service->groupedByStandard($search);

        return view('label_skus.index', compact('labelSkusByStandard', 'search'));
    }

    public function create(): View
    {
        $serialStandard = strtoupper(trim((string) request('serial_standard', 'UL')));
        if (!in_array($serialStandard, ['UL', 'EMEA', 'ANZ'], true)) {
            $serialStandard = 'UL';
        }

        return view('label_skus.create', compact('serialStandard'));
    }

    public function store(StoreLabelSkuRequest $request): RedirectResponse
    {
        $this->service->create($request->validated(), auth()->id());

        return redirect()->route('label_skus.index')
            ->with('success', 'SKU agregado correctamente.');
    }

    public function edit(LabelSku $label_sku): View
    {
        return view('label_skus.edit', ['labelSku' => $label_sku]);
    }

    public function update(UpdateLabelSkuRequest $request, LabelSku $label_sku): RedirectResponse
    {
        $this->service->update($label_sku, $request->validated(), auth()->id());

        return redirect()->route('label_skus.index')
            ->with('success', 'SKU actualizado correctamente.');
    }

    public function toggle(LabelSku $label_sku): RedirectResponse
    {
        $this->service->toggleActive($label_sku, auth()->id());

        return redirect()->route('label_skus.index')
            ->with('success', 'Estado actualizado.');
    }
}
