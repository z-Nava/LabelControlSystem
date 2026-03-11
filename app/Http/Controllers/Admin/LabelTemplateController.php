<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreLabelTemplateRequest;
use App\Http\Requests\Admin\UpdateLabelTemplateRequest;
use App\Models\LabelSku;
use App\Models\LabelTemplate;
use App\Models\SkuSerialFormat;
use App\Services\Catalogs\LabelTemplateService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Collection;
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
        $labelSkus = $this->availableLabelSkus();
        $serialFormatPreviews = $this->buildSerialFormatPreviews($labelSkus->pluck('sku'));

        return view('label_templates.create', compact('labelSkus', 'serialFormatPreviews'));
    }

    public function store(StoreLabelTemplateRequest $request): RedirectResponse
    {
        $this->service->create($request->validated(), auth()->id());

        return redirect()->route('label_templates.index')->with('success', 'Template ZPL creado correctamente.');
    }

    public function edit(LabelTemplate $label_template): View
    {
        $labelSkus = $this->availableLabelSkus($label_template->label_sku_id);

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

    private function availableLabelSkus(?int $selectedLabelSkuId = null): Collection
    {
        $configuredSkus = SkuSerialFormat::query()
            ->active()
            ->distinct()
            ->pluck('sku');

        return LabelSku::query()
            ->where(function ($query) use ($configuredSkus, $selectedLabelSkuId) {
                $query->where(function ($activeQuery) use ($configuredSkus) {
                    $activeQuery->active();

                    if ($configuredSkus->isNotEmpty()) {
                        $activeQuery->whereIn('sku', $configuredSkus);
                    }
                });

                if ($selectedLabelSkuId !== null) {
                    $query->orWhere('id', $selectedLabelSkuId);
                }
            })
            ->orderBy('sku')
            ->get();
    }

    private function buildSerialFormatPreviews(Collection $skus): array
    {
        if ($skus->isEmpty()) {
            return [];
        }

        $year = now()->year;
        $week = (int) now()->isoWeek();

        return SkuSerialFormat::query()
            ->active()
            ->whereIn('sku', $skus)
            ->orderBy('id')
            ->get()
            ->unique('sku')
            ->mapWithKeys(function (SkuSerialFormat $format) use ($year, $week) {
                $serial = str_pad((string) max(1, (int) ($format->next_unit ?? 1)), max(1, (int) ($format->unit_length ?? 5)), '0', STR_PAD_LEFT);
                $yearValue = ((int) ($format->year_digits ?? 2)) >= 4
                    ? str_pad(substr((string) $year, -4), 4, '0', STR_PAD_LEFT)
                    : str_pad(substr((string) $year, -2), 2, '0', STR_PAD_LEFT);
                $weekValue = str_pad((string) $week, max(1, (int) ($format->week_digits ?? 2)), '0', STR_PAD_LEFT);

                if (!empty($format->pattern)) {
                    $pattern = preg_replace('/\{\{\s*(PPP|C|PL|YY|WW|SSSSS)\s*\}\}/', '{$1}', (string) $format->pattern) ?? (string) $format->pattern;
                    if (!str_contains($pattern, '{SSSSS}')) {
                        $pattern .= '{SSSSS}';
                    }

                    $preview = strtr($pattern, [
                        '{PPP}' => (string) ($format->prefix ?? ''),
                        '{C}' => (string) ($format->serial_break ?? ''),
                        '{PL}' => (string) ($format->plant_code ?? ''),
                        '{YY}' => $yearValue,
                        '{WW}' => $weekValue,
                        '{SSSSS}' => $serial,
                    ]);
                } else {
                    $pieces = [
                        (string) ($format->prefix ?? ''),
                        (string) ($format->serial_break ?? ''),
                        (string) ($format->plant_code ?? ''),
                    ];

                    if ((bool) ($format->include_year ?? true)) {
                        $pieces[] = $yearValue;
                    }

                    if ((bool) ($format->include_week ?? true)) {
                        $pieces[] = $weekValue;
                    }

                    $pieces[] = $serial;

                    $preview = collect($pieces)
                        ->filter(fn (string $piece) => $piece !== '')
                        ->implode((string) ($format->separator ?? ''));
                }

                return [$format->sku => $preview];
            })
            ->all();
    }
}
