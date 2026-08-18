<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreMasterAssemblyClassificationRuleRequest;
use App\Http\Requests\Admin\UpdateMasterAssemblyClassificationRuleRequest;
use App\Models\MasterAssemblyClassificationRule;
use App\Services\Catalogs\MasterAssemblyClassificationRuleService;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class MasterAssemblyClassificationRuleController extends Controller
{
    public function __construct(private readonly MasterAssemblyClassificationRuleService $service) {}

    public function index(): View
    {
        $search = request('q');
        $rules = $this->service->paginate(20, $search);

        return view('admin.master_assembly_classification_rules.index', compact('rules', 'search'));
    }

    public function create(): View
    {
        return view('admin.master_assembly_classification_rules.create');
    }

    public function store(StoreMasterAssemblyClassificationRuleRequest $request): RedirectResponse
    {
        $this->service->create($request->validated());

        return redirect()->route('admin.master_assembly_classification_rules.index')
            ->with('success', 'Master Assembly Classification Rule creada correctamente.');
    }

    public function edit(MasterAssemblyClassificationRule $assembly_rule): View
    {
        return view('admin.master_assembly_classification_rules.edit', [
            'rule' => $assembly_rule,
        ]);
    }

    public function update(
        UpdateMasterAssemblyClassificationRuleRequest $request,
        MasterAssemblyClassificationRule $assembly_rule,
    ): RedirectResponse {
        $this->service->update($assembly_rule, $request->validated());

        return redirect()->route('admin.master_assembly_classification_rules.index')
            ->with('success', 'Master Assembly Classification Rule actualizada correctamente.');
    }

    public function toggle(MasterAssemblyClassificationRule $assembly_rule): RedirectResponse
    {
        $this->service->toggleActive($assembly_rule);

        return redirect()->route('admin.master_assembly_classification_rules.index')
            ->with('success', 'Estado actualizado.');
    }
}
