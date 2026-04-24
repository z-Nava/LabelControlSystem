<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\ImportMasterModelMappingsRequest;
use App\Http\Requests\Admin\StoreMasterModelMappingRequest;
use App\Http\Requests\Admin\UpdateMasterModelMappingRequest;
use App\Models\MasterModelMapping;
use App\Services\Catalogs\MasterModelMappingService;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class MasterModelMappingController extends Controller
{
    public function __construct(private readonly MasterModelMappingService $service)
    {
    }

    public function index(string $type): View
    {
        abort_unless(in_array($type, MasterModelMapping::TYPES, true), 404);

        $search = request('q');
        $mappings = $this->service->paginateByType($type, 20, $search);

        return view('master_model_mappings.index', [
            'type' => $type,
            'typeLabel' => MasterModelMapping::labelForType($type),
            'mappings' => $mappings,
            'search' => $search,
        ]);
    }

    public function create(string $type): View
    {
        abort_unless(in_array($type, MasterModelMapping::TYPES, true), 404);

        return view('master_model_mappings.create', [
            'type' => $type,
            'typeLabel' => MasterModelMapping::labelForType($type),
        ]);
    }

    public function store(StoreMasterModelMappingRequest $request, string $type): RedirectResponse
    {
        $this->service->create($request->validated(), $type);

        return redirect()->route('master_model_mappings.index', $type)
            ->with('success', 'Registro creado correctamente.');
    }

    public function edit(string $type, MasterModelMapping $master_model_mapping): View
    {
        abort_unless($master_model_mapping->master_sheet_type === $type, 404);

        return view('master_model_mappings.edit', [
            'type' => $type,
            'typeLabel' => MasterModelMapping::labelForType($type),
            'mapping' => $master_model_mapping,
        ]);
    }

    public function update(UpdateMasterModelMappingRequest $request, string $type, MasterModelMapping $master_model_mapping): RedirectResponse
    {
        abort_unless($master_model_mapping->master_sheet_type === $type, 404);

        $this->service->update($master_model_mapping, $request->validated(), $type);

        return redirect()->route('master_model_mappings.index', $type)
            ->with('success', 'Registro actualizado correctamente.');
    }

    public function toggle(string $type, MasterModelMapping $master_model_mapping): RedirectResponse
    {
        abort_unless($master_model_mapping->master_sheet_type === $type, 404);

        $this->service->toggleActive($master_model_mapping);

        return redirect()->route('master_model_mappings.index', $type)
            ->with('success', 'Estado actualizado.');
    }

    public function import(ImportMasterModelMappingsRequest $request, string $type): RedirectResponse
    {
        abort_unless(in_array($type, MasterModelMapping::TYPES, true), 404);

        $result = $this->service->importFromExcel($request->file('file'), $type);

        return redirect()->route('master_model_mappings.index', $type)
            ->with('success', "Importación finalizada. Insertados: {$result['inserted']}, Reactivados/actualizados: {$result['updated']}, Omitidos: {$result['skipped']}.");
    }
}
