<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreDummyQrTemplateRequest;
use App\Http\Requests\Admin\UpdateDummyQrTemplateRequest;
use App\Models\DummyQrTemplate;
use App\Services\DummyQr\DummyQrTemplateZplBuilder;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class DummyQrTemplateController extends Controller
{
    public function __construct(
        private readonly DummyQrTemplateZplBuilder $zplBuilder,
    ) {}

    public function index(): View
    {
        $search = trim((string) request('q', ''));

        $templates = DummyQrTemplate::query()
            ->when($search !== '', function ($query) use ($search) {
                $query->where('name', 'like', "%{$search}%")
                    ->orWhere('dummy_type', 'like', "%{$search}%")
                    ->orWhere('default_printer_name', 'like', "%{$search}%");
            })
            ->orderByDesc('is_active')
            ->orderBy('dummy_type')
            ->orderByDesc('updated_at')
            ->paginate(15)
            ->withQueryString();

        return view('admin.dummy_qr_templates.index', compact('templates', 'search'));
    }

    public function create(): View
    {
        return view('admin.dummy_qr_templates.create', [
            'template' => new DummyQrTemplate(),
        ]);
    }

    public function store(StoreDummyQrTemplateRequest $request): RedirectResponse
    {
        $data = $request->validated();
        $data['zpl'] = $this->zplBuilder->build($data);
        $data['created_by_user_id'] = auth()->id();
        $data['updated_by_user_id'] = auth()->id();

        $template = DummyQrTemplate::query()->create($data);

        if ($template->is_active) {
            $this->deactivateOtherActive($template);
        }

        return redirect()->route('admin.dummy_qr_templates.index')
            ->with('success', 'Template Dummy QR creado correctamente.');
    }

    public function edit(DummyQrTemplate $dummy_qr_template): View
    {
        return view('admin.dummy_qr_templates.edit', [
            'template' => $dummy_qr_template,
        ]);
    }

    public function update(UpdateDummyQrTemplateRequest $request, DummyQrTemplate $dummy_qr_template): RedirectResponse
    {
        $data = $request->validated();
        $data['zpl'] = $this->zplBuilder->build($data);
        $data['updated_by_user_id'] = auth()->id();

        $dummy_qr_template->update($data);

        if ($dummy_qr_template->is_active) {
            $this->deactivateOtherActive($dummy_qr_template);
        }

        return redirect()->route('admin.dummy_qr_templates.index')
            ->with('success', 'Template Dummy QR actualizado correctamente.');
    }

    public function toggle(DummyQrTemplate $dummy_qr_template): RedirectResponse
    {
        $dummy_qr_template->update([
            'is_active' => !$dummy_qr_template->is_active,
            'updated_by_user_id' => auth()->id(),
        ]);

        if ($dummy_qr_template->is_active) {
            $this->deactivateOtherActive($dummy_qr_template);
        }

        return redirect()->route('admin.dummy_qr_templates.index')
            ->with('success', 'Estado del template Dummy QR actualizado.');
    }

    private function deactivateOtherActive(DummyQrTemplate $template): void
    {
        DummyQrTemplate::query()
            ->where('id', '!=', $template->id)
            ->where('dummy_type', $template->dummy_type)
            ->where('is_active', true)
            ->update([
                'is_active' => false,
                'updated_by_user_id' => auth()->id(),
            ]);
    }
}
