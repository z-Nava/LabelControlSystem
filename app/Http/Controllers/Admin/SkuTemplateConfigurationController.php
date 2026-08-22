<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\IndexSkuTemplateConfigurationRequest;
use App\Http\Requests\Admin\StoreSkuTemplateConfigurationRequest;
use App\Http\Requests\Admin\UpdateSkuTemplateConfigurationRequest;
use App\Models\LabelPrintProfile;
use App\Services\Catalogs\SkuTemplateConfigurationFormService;
use App\Services\Catalogs\SkuTemplateConfigurationReadService;
use App\Services\Catalogs\SkuTemplateConfigurationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class SkuTemplateConfigurationController extends Controller
{
    private const CREATE_VIEWS = [
        'UL' => 'admin.sku_template_configurations.create_ul',
        'EMEA' => 'admin.sku_template_configurations.create_emea',
        'ANZ' => 'admin.sku_template_configurations.create_anz',
    ];

    public function __construct(
        private readonly SkuTemplateConfigurationReadService $readService,
        private readonly SkuTemplateConfigurationService $configurationService,
        private readonly SkuTemplateConfigurationFormService $formService,
    ) {}

    public function index(IndexSkuTemplateConfigurationRequest $request): View
    {
        return view(
            'admin.sku_template_configurations.index',
            $this->readService->paginateForIndex($request->validated()),
        );
    }

    public function create(): View
    {
        return $this->createByStandard('UL');
    }

    public function createByStandard(string $standard): View
    {
        $standard = strtoupper($standard);
        $view = self::CREATE_VIEWS[$standard] ?? self::CREATE_VIEWS['UL'];

        return view($view, $this->formService->buildForCreate($standard));
    }

    public function store(StoreSkuTemplateConfigurationRequest $request): RedirectResponse
    {
        $this->configurationService->create($request->validated(), $request->user()?->id);

        return redirect()->route('admin.sku_template_configurations.index')
            ->with('success', 'Configuración de template + print profile creada correctamente.');
    }

    public function edit(LabelPrintProfile $configuration): View
    {
        return view(
            'admin.sku_template_configurations.edit',
            $this->formService->buildForEdit($configuration),
        );
    }

    public function update(
        UpdateSkuTemplateConfigurationRequest $request,
        LabelPrintProfile $configuration,
    ): RedirectResponse {
        $this->configurationService->update(
            $configuration,
            $request->validated(),
            $request->user()?->id,
        );

        return redirect()->route('admin.sku_template_configurations.index')
            ->with('success', 'Configuración actualizada correctamente.');
    }

    public function toggle(Request $request, LabelPrintProfile $configuration): RedirectResponse
    {
        $this->configurationService->toggleActive($configuration, $request->user()?->id);

        return redirect()->route('admin.sku_template_configurations.index')
            ->with('success', 'Estado de la configuración actualizado.');
    }
}
