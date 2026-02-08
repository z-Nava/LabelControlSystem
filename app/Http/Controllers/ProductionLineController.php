<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreProductionLineRequest;
use App\Http\Requests\UpdateProductionLineRequest;
use App\Models\ProductionLine;
use App\Services\Catalogs\ProductionLineService;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class ProductionLineController extends Controller
{
    public function __construct(private readonly ProductionLineService $service)
    {
    }

    public function index(): View
    {
        $search = request('q');
        $lines = $this->service->paginate(15, $search);

        return view('production_lines.index', compact('lines', 'search'));
    }

    public function create(): View
    {
        return view('production_lines.create');
    }

    public function store(StoreProductionLineRequest $request): RedirectResponse
    {
        $this->service->create($request->validated());

        return redirect()->route('production_lines.index')
            ->with('success', 'Línea creada correctamente.');
    }

    public function edit(ProductionLine $production_line): View
    {
        return view('production_lines.edit', ['line' => $production_line]);
    }

    public function update(UpdateProductionLineRequest $request, ProductionLine $production_line): RedirectResponse
    {
        $this->service->update($production_line, $request->validated());

        return redirect()->route('production_lines.index')
            ->with('success', 'Línea actualizada correctamente.');
    }

    public function toggle(ProductionLine $production_line): RedirectResponse
    {
        $this->service->toggleActive($production_line);

        return redirect()->route('production_lines.index')
            ->with('success', 'Estado actualizado.');
    }
}
