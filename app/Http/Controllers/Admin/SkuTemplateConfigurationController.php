<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreSkuTemplateConfigurationRequest;
use App\Http\Requests\Admin\UpdateSkuTemplateConfigurationRequest;
use App\Models\LabelPrintProfile;
use App\Services\Catalogs\LabelPrintProfileService;
use App\Services\Catalogs\LabelTemplateService;
use App\Services\Catalogs\SkuTemplateConfigurationFormService;
use App\Services\Labels\SerialTemplateZplBuilder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class SkuTemplateConfigurationController extends Controller
{
    public function __construct(
        private readonly LabelTemplateService $templateService,
        private readonly LabelPrintProfileService $profileService,
        private readonly SerialTemplateZplBuilder $zplBuilder,
        private readonly SkuTemplateConfigurationFormService $formService,
    ) {
    }

    public function index(): View
    {
        $search = request('q');
        $sort = request('sort', 'sku');
        $serialStandard = strtoupper(trim((string) request('serial_standard', 'ALL')));

        $sorts = [
            'sku' => 'SKU (A → Z)',
            'type' => 'Tipo de etiqueta',
            'updated' => 'Última actualización',
        ];

        if (!array_key_exists($sort, $sorts)) {
            $sort = 'sku';
        }

        if (!in_array($serialStandard, ['ALL', 'UL', 'EMEA', 'ANZ'], true)) {
            $serialStandard = 'ALL';
        }

        $configs = LabelPrintProfile::query()
            ->with(['sku', 'template'])
            ->leftJoin('label_skus', 'label_skus.id', '=', 'label_print_profiles.label_sku_id')
            ->select('label_print_profiles.*')
            ->when($search, function ($query) use ($search) {
                $query->where('name', 'like', "%{$search}%")
                    ->orWhere('label_type', 'like', "%{$search}%")
                    ->orWhereHas('sku', fn ($skuQuery) => $skuQuery
                        ->where('sku', 'like', "%{$search}%")
                        ->orWhere('label_part_number', 'like', "%{$search}%"));
            })
            ->when($serialStandard !== 'ALL', fn ($query) => $query->where('label_skus.serial_standard', $serialStandard))
            ->when($sort === 'sku', function ($query) {
                $query->orderByRaw("CASE WHEN label_skus.sku IS NULL OR label_skus.sku = '' THEN 1 ELSE 0 END")
                    ->orderBy('label_skus.sku')
                    ->orderBy('label_skus.label_part_number')
                    ->orderBy('label_print_profiles.label_type')
                    ->orderBy('label_print_profiles.name');
            })
            ->when($sort === 'type', function ($query) {
                $query->orderBy('label_print_profiles.label_type')
                    ->orderBy('label_skus.sku')
                    ->orderBy('label_print_profiles.name');
            })
            ->when($sort === 'updated', fn ($query) => $query->orderByDesc('label_print_profiles.updated_at'))
            ->orderByDesc('label_print_profiles.is_active')
            ->paginate(15)
            ->withQueryString();

        return view('admin.sku_template_configurations.index', compact('configs', 'search', 'sort', 'sorts', 'serialStandard'));
    }

    public function create(): View
    {
        return $this->buildCreateView('UL');
    }

    public function createByStandard(string $standard): View
    {
        return $this->buildCreateView(strtoupper($standard));
    }

    private function buildCreateView(string $forcedStandard): View
    {
        $configuration = new LabelPrintProfile();
        $formData = $this->formService->build($configuration);
        $formData['formState']['selected_serial_standard'] = $forcedStandard;
        $formData['forcedStandard'] = $forcedStandard;

        $viewByStandard = [
            'UL' => 'admin.sku_template_configurations.create_ul',
            'EMEA' => 'admin.sku_template_configurations.create_emea',
            'ANZ' => 'admin.sku_template_configurations.create_anz',
        ];

        $view = $viewByStandard[$forcedStandard] ?? $viewByStandard['UL'];

        return view($view, compact('configuration') + $formData);
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
        $formData = $this->formService->build($configuration);

        return view('admin.sku_template_configurations.edit', compact('configuration') + $formData);
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
        $layout = $this->buildTemplateLayout($data);

        return [
            'name' => $data['template_name'],
            'label_type' => $data['label_type'],
            'serial_standard' => $data['serial_standard'],
            'label_sku_id' => $data['label_sku_id'],
            'dpi' => $data['template_dpi'],
            'width_mm' => $data['template_width_mm'] ?? null,
            'height_mm' => $data['template_height_mm'] ?? null,
            'zpl' => $this->zplBuilder->build($data['label_type'], $layout, $data['serial_standard']),
            'serial_layout' => $layout,
            'meta' => [
                'serial_layout' => $layout,
            ],
            'is_active' => $data['template_is_active'],
        ];
    }

    private function buildTemplateLayout(array $data): array
    {
        return [
            'text' => [
                'x' => $data['serial_position_x'],
                'y' => $data['serial_position_y'],
                'font_size' => $data['serial_font_size'],
                'orientation' => $data['serial_orientation'],
            ],
            'qr' => [
                'x' => $data['qr_position_x'] ?? 30,
                'y' => $data['qr_position_y'] ?? 30,
                'orientation' => $data['qr_orientation'] ?? 'N',
                'magnification' => $data['qr_magnification'] ?? 4,
                'content_mode' => $data['qr_content_mode'] ?? 'auto',
                'separator' => $data['qr_separator'] ?? 'pipe',
                'serial_style' => $data['qr_serial_style'] ?? 'as_is',
                'custom_fields' => array_values(array_filter([
                    $data['qr_custom_field_1'] ?? null,
                    $data['qr_custom_field_2'] ?? null,
                    $data['qr_custom_field_3'] ?? null,
                ])),
            ],
            'rating_qr' => (bool) ($data['rating_with_qr'] ?? false),
            'rating_hide_sku' => (bool) ($data['rating_hide_sku'] ?? false),
            'sku' => [
                'x' => $data['sku_position_x'] ?? 170,
                'y' => $data['sku_position_y'] ?? 35,
                'font_size' => $data['sku_font_size'] ?? 44,
                'orientation' => $data['sku_orientation'] ?? 'N',
            ],
            'sn' => [
                'x' => $data['sn_position_x'] ?? 170,
                'y' => $data['sn_position_y'] ?? 95,
                'font_size' => $data['sn_font_size'] ?? 22,
                'orientation' => $data['sn_orientation'] ?? 'N',
                'prefix' => $data['sn_prefix'] ?? 'SN:',
            ],
        ];
    }

    private function profilePayload(array $data, int $templateId): array
    {
        return [
            'label_sku_id' => $data['label_sku_id'],
            'label_type' => $data['label_type'],
            'serial_standard' => $data['serial_standard'],
            'label_template_id' => $templateId,
            'name' => $data['profile_name'],
            'default_printer_name' => $data['default_printer_name'] ?? null,
            'default_printer_ip' => $data['connection_type'] === 'network' ? ($data['default_printer_ip'] ?? null) : null,
            'dpi' => $data['profile_dpi'],
            'darkness' => $data['darkness'] ?? null,
            'speed' => $data['speed'] ?? null,
            'media_type' => $data['media_type'] ?? null,
            'media_tracking' => $data['media_tracking'] ?? null,
            'print_mode' => $data['print_mode'] ?? null,
            'offset_x' => $data['offset_x'] ?? 0,
            'offset_y' => $data['offset_y'] ?? 0,
            'settings' => [
                'connection_type' => $data['connection_type'],
                'usb_required' => $data['connection_type'] === 'usb',
            ],
            'is_active' => $data['profile_is_active'],
        ];
    }

}
