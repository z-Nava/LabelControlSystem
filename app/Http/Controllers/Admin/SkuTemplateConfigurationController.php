<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreSkuTemplateConfigurationRequest;
use App\Http\Requests\Admin\UpdateSkuTemplateConfigurationRequest;
use App\Models\LabelPrintProfile;
use App\Models\LabelSku;
use App\Services\Catalogs\LabelPrintProfileService;
use App\Services\Catalogs\LabelTemplateService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class SkuTemplateConfigurationController extends Controller
{
    public function __construct(
        private readonly LabelTemplateService $templateService,
        private readonly LabelPrintProfileService $profileService,
    ) {
    }

    public function index(): View
    {
        $search = request('q');

        $configs = LabelPrintProfile::query()
            ->with(['sku', 'template'])
            ->when($search, function ($query) use ($search) {
                $query->where('name', 'like', "%{$search}%")
                    ->orWhere('label_type', 'like', "%{$search}%")
                    ->orWhereHas('sku', fn ($skuQuery) => $skuQuery
                        ->where('sku', 'like', "%{$search}%")
                        ->orWhere('label_part_number', 'like', "%{$search}%"));
            })
            ->orderByDesc('is_active')
            ->orderByDesc('updated_at')
            ->paginate(15)
            ->withQueryString();

        return view('admin.sku_template_configurations.index', compact('configs', 'search'));
    }

    public function create(): View
    {
        $labelSkus = LabelSku::query()->active()->orderBy('sku')->get();

        return view('admin.sku_template_configurations.create', compact('labelSkus'));
    }

    public function store(StoreSkuTemplateConfigurationRequest $request): RedirectResponse
    {
        $data = $request->validated();

        DB::transaction(function () use ($data): void {
            $template = $this->templateService->create($this->templatePayload($data), auth()->id());
            $this->profileService->create($this->profilePayload($data, $template->id), auth()->id());
        });

        return redirect()->route('admin.sku_template_configurations.index')
            ->with('success', 'Configuración de template + print profile creada correctamente.');
    }

    public function edit(LabelPrintProfile $configuration): View
    {
        $configuration->load('template');
        $labelSkus = LabelSku::query()->active()->orderBy('sku')->get();

        return view('admin.sku_template_configurations.edit', compact('configuration', 'labelSkus'));
    }

    public function update(UpdateSkuTemplateConfigurationRequest $request, LabelPrintProfile $configuration): RedirectResponse
    {
        $data = $request->validated();

        DB::transaction(function () use ($data, $configuration): void {
            $template = $configuration->template;

            if (!$template) {
                $template = $this->templateService->create($this->templatePayload($data), auth()->id());
            } else {
                $this->templateService->update($template, $this->templatePayload($data), auth()->id());
            }

            $this->profileService->update($configuration, $this->profilePayload($data, $template->id), auth()->id());
        });

        return redirect()->route('admin.sku_template_configurations.index')
            ->with('success', 'Configuración actualizada correctamente.');
    }

    public function toggle(LabelPrintProfile $configuration): RedirectResponse
    {
        $this->profileService->toggleActive($configuration, auth()->id());

        if ($configuration->template) {
            $this->templateService->toggleActive($configuration->template, auth()->id());
        }

        return redirect()->route('admin.sku_template_configurations.index')
            ->with('success', 'Estado de la configuración actualizado.');
    }

    private function templatePayload(array $data): array
    {
        return [
            'name' => $data['template_name'],
            'label_type' => $data['label_type'],
            'label_sku_id' => $data['label_sku_id'],
            'dpi' => $data['template_dpi'],
            'width_mm' => $data['template_width_mm'] ?? null,
            'height_mm' => $data['template_height_mm'] ?? null,
            'zpl' => $data['template_zpl'],
            'meta' => $data['template_meta'] ?? null,
            'is_active' => $data['template_is_active'],
        ];
    }

    private function profilePayload(array $data, int $templateId): array
    {
        return [
            'label_sku_id' => $data['label_sku_id'],
            'label_type' => $data['label_type'],
            'label_template_id' => $templateId,
            'name' => $data['profile_name'],
            'default_printer_name' => $data['default_printer_name'] ?? null,
            'default_printer_ip' => $data['default_printer_ip'] ?? null,
            'dpi' => $data['profile_dpi'],
            'darkness' => $data['darkness'] ?? null,
            'speed' => $data['speed'] ?? null,
            'media_type' => $data['media_type'] ?? null,
            'media_tracking' => $data['media_tracking'] ?? null,
            'print_mode' => $data['print_mode'] ?? null,
            'offset_x' => $data['offset_x'] ?? 0,
            'offset_y' => $data['offset_y'] ?? 0,
            'settings' => $data['profile_settings'] ?? null,
            'is_active' => $data['profile_is_active'],
        ];
    }
}
