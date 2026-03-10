<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreLabelTemplateRequest;
use App\Http\Requests\Admin\UpdateLabelTemplateRequest;
use App\Models\LabelSku;
use App\Models\LabelTemplate;
use App\Services\Catalogs\LabelTemplateService;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class LabelTemplateController extends Controller
{
    public function __construct(private readonly LabelTemplateService $service)
    {
    }

    public function index(): View
    {
        $search = request('q');
        $templates = $this->service->paginate(15, $search);

        return view('label_templates.index', compact('templates', 'search'));
    }

    public function create(): View
    {
        $labelSkus = LabelSku::query()->active()->orderBy('sku')->get();

        return view('label_templates.create', compact('labelSkus'));
    }

    public function store(StoreLabelTemplateRequest $request): RedirectResponse
    {
        $this->service->create($request->validated(), auth()->id());

        return redirect()->route('label_templates.index')->with('success', 'Template ZPL creado correctamente.');
    }

    public function edit(LabelTemplate $label_template): View
    {
        $labelSkus = LabelSku::query()->active()->orderBy('sku')->get();

        return view('label_templates.edit', ['template' => $label_template, 'labelSkus' => $labelSkus]);
    }

    public function update(UpdateLabelTemplateRequest $request, LabelTemplate $label_template): RedirectResponse
    {
        $this->service->update($label_template, $request->validated(), auth()->id());

        return redirect()->route('label_templates.index')->with('success', 'Template ZPL actualizado correctamente.');
    }

    public function toggle(LabelTemplate $label_template): RedirectResponse
    {
        $this->service->toggleActive($label_template, auth()->id());

        return redirect()->route('label_templates.index')->with('success', 'Estado del template actualizado.');
    }
}
