<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\ImportRatingAssemblyMappingsRequest;
use App\Http\Requests\Admin\StoreRatingAssemblyMappingRequest;
use App\Http\Requests\Admin\UpdateRatingAssemblyMappingRequest;
use App\Models\RatingAssemblyMapping;
use App\Services\Catalogs\RatingAssemblyMappingService;
use App\Support\SerialStandards;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class RatingAssemblyMappingController extends Controller
{
    public function __construct(private readonly RatingAssemblyMappingService $service) {}

    public function index(Request $request): View
    {
        $filters = $request->validate([
            'q' => ['nullable', 'string', 'max:100'],
            'market' => ['nullable', 'string', 'in:UL,EMEA,ANZ,APJ'],
            'status' => ['nullable', 'in:active,inactive'],
        ]);

        return view('rating_assembly_mappings.index', [
            'mappings' => $this->service->paginate(
                $filters['q'] ?? null,
                $filters['market'] ?? null,
                isset($filters['status']) ? $filters['status'] === 'active' : null,
            ),
            'filters' => $filters,
            'markets' => SerialStandards::all(),
        ]);
    }

    public function create(): View
    {
        return view('rating_assembly_mappings.create', ['markets' => SerialStandards::all()]);
    }

    public function store(StoreRatingAssemblyMappingRequest $request): RedirectResponse
    {
        $this->service->create($request->validated(), $request->user()?->id);

        return redirect()->route('rating_assembly_mappings.index')->with('success', 'Relación creada correctamente.');
    }

    public function edit(RatingAssemblyMapping $rating_assembly_mapping): View
    {
        return view('rating_assembly_mappings.edit', [
            'mapping' => $rating_assembly_mapping,
            'markets' => SerialStandards::all(),
        ]);
    }

    public function update(UpdateRatingAssemblyMappingRequest $request, RatingAssemblyMapping $rating_assembly_mapping): RedirectResponse
    {
        $this->service->update($rating_assembly_mapping, $request->validated(), $request->user()?->id);

        return redirect()->route('rating_assembly_mappings.index')->with('success', 'Relación actualizada correctamente.');
    }

    public function toggle(Request $request, RatingAssemblyMapping $rating_assembly_mapping): RedirectResponse
    {
        $this->service->toggleActive($rating_assembly_mapping, $request->user()?->id);

        return back()->with('success', 'Estado actualizado.');
    }

    public function import(ImportRatingAssemblyMappingsRequest $request): RedirectResponse
    {
        $result = $this->service->importFromExcel($request->file('file'), $request->user()?->id);

        return back()->with('success', "Importación finalizada. Insertados: {$result['inserted']}, reactivados: {$result['updated']}, omitidos: {$result['skipped']}.");
    }
}
