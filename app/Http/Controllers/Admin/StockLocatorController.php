<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreStockLocatorRequest;
use App\Http\Requests\Admin\UpdateStockLocatorRequest;
use App\Models\StockLocator;
use App\Services\Catalogs\StockLocatorService;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class StockLocatorController extends Controller
{
    public function __construct(private readonly StockLocatorService $service)
    {
    }

    public function index(): View
    {
        $search = request('q');
        $stockLocators = $this->service->paginate(20, $search);

        return view('stock_locators.index', compact('stockLocators', 'search'));
    }

    public function create(): View
    {
        return view('stock_locators.create');
    }

    public function store(StoreStockLocatorRequest $request): RedirectResponse
    {
        $this->service->create($request->validated());

        return redirect()->route('stock_locators.index')
            ->with('success', 'Local/Línea creada correctamente.');
    }

    public function edit(StockLocator $stock_locator): View
    {
        return view('stock_locators.edit', ['stockLocator' => $stock_locator]);
    }

    public function update(UpdateStockLocatorRequest $request, StockLocator $stock_locator): RedirectResponse
    {
        $this->service->update($stock_locator, $request->validated());

        return redirect()->route('stock_locators.index')
            ->with('success', 'Local/Línea actualizada correctamente.');
    }

    public function toggle(StockLocator $stock_locator): RedirectResponse
    {
        $this->service->toggleActive($stock_locator);

        return redirect()->route('stock_locators.index')
            ->with('success', 'Estado actualizado.');
    }
}
