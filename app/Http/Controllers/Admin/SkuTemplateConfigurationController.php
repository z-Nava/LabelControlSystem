<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreSkuTemplateConfigurationRequest;
use App\Http\Requests\Admin\UpdateSkuTemplateConfigurationRequest;
use App\Models\LabelPrintProfile;
use App\Models\LabelSku;
use App\Models\SkuSerialFormat;
use App\Services\Catalogs\LabelPrintProfileService;
use App\Services\Catalogs\LabelTemplateService;
use App\Services\Labels\SerialTemplateZplBuilder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class SkuTemplateConfigurationController extends Controller
{
    public function __construct(
        private readonly LabelTemplateService $templateService,
        private readonly LabelPrintProfileService $profileService,
        private readonly SerialTemplateZplBuilder $zplBuilder,
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
        $labelSkus = $this->skuOptionsWithSerialFormat();

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
        $labelSkus = $this->skuOptionsWithSerialFormat();

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

    private function skuOptionsWithSerialFormat(): Collection
    {
        return LabelSku::query()
            ->active()
            ->whereIn('sku', SkuSerialFormat::query()->active()->select('sku'))
            ->orderBy('sku')
            ->get();
    }

    private function templatePayload(array $data): array
    {
        $layout = [
            'text' => [
                'x' => $data['serial_position_x'],
                'y' => $data['serial_position_y'],
                'font_size' => $data['serial_font_size'],
                'orientation' => $data['serial_orientation'],
            ],
            'qr' => [
                'x' => $data['qr_position_x'] ?? 30,
                'y' => $data['qr_position_y'] ?? 30,
                'magnification' => $data['qr_magnification'] ?? 4,
            ],
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

        return [
            'name' => $data['template_name'],
            'label_type' => $data['label_type'],
            'label_sku_id' => $data['label_sku_id'],
            'dpi' => $data['template_dpi'],
            'width_mm' => $data['template_width_mm'] ?? null,
            'height_mm' => $data['template_height_mm'] ?? null,
            'zpl' => $this->zplBuilder->build($data['label_type'], $layout),
            'meta' => [
                'serial_layout' => $layout,
            ],
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
