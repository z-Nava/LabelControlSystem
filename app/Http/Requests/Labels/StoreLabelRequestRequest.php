<?php

namespace App\Http\Requests\Labels;

use App\Models\LabelSku;
use App\Models\SkuSerialFormat;
use App\Services\Oracle\OracleJobLookupService;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class StoreLabelRequestRequest extends FormRequest
{
    private ?OracleJobLookupService $oracleJobLookup = null;

    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'request_date' => ['required', 'date', 'before_or_equal:today'],
            'week' => ['required', 'integer', 'min:1', 'max:53'],
            'line_id' => ['required', 'integer', 'exists:production_lines,id'],
            'shift_id' => ['required', 'integer', 'exists:shifts,id'],
            'leader_name' => ['required', 'string', 'min:3', 'max:120', 'regex:/^[\pL\s\-.\x27"]+$/u'],
            'label_part_number' => ['required', 'string', 'max:80'],
            'quantity_requested' => ['required', 'integer', 'min:1', 'max:100000'],
            'include_serial' => ['nullable', 'boolean'],
            'include_rating' => ['nullable', 'boolean'],
            'job_number' => ['nullable', 'string', 'max:40', 'regex:/^[0-9A-Za-z\-]+$/'],
            'po_number' => ['nullable', 'string', 'max:80', 'regex:/^[A-Za-z0-9\-\/_\s]+$/'],
            'destination' => ['nullable', 'string', 'max:80', 'regex:/^[A-Za-z0-9\-\/_\s]+$/'],
            'model' => ['nullable', 'string', 'max:80'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'include_serial' => $this->boolean('include_serial'),
            'include_rating' => $this->boolean('include_rating'),
            'label_part_number' => strtoupper(trim((string) $this->input('label_part_number'))),
            'job_number' => strtoupper(trim((string) $this->input('job_number'))),
            'po_number' => strtoupper(trim((string) $this->input('po_number'))),
            'destination' => strtoupper(trim((string) $this->input('destination'))),
            'model' => strtoupper(trim((string) $this->input('model'))),
        ]);
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            if (!$this->boolean('include_serial') && !$this->boolean('include_rating')) {
                $validator->errors()->add('include_serial', 'Debes seleccionar al menos un tipo de etiqueta (Serial o Rating).');
            }

            $labelPn = (string) $this->input('label_part_number');
            if ($labelPn !== '') {
                $labelSku = LabelSku::query()
                    ->where('label_part_number', $labelPn)
                    ->where('is_active', true)
                    ->first(['sku']);

                if (!$labelSku) {
                    $validator->errors()->add('label_part_number', 'El Label PN debe existir y estar activo en el catálogo SKU/NP.');
                } else {
                    $hasActiveFormat = SkuSerialFormat::query()
                        ->where('sku', $labelSku->sku)
                        ->where('is_active', true)
                        ->exists();

                    if (!$hasActiveFormat) {
                        $validator->errors()->add('label_part_number', 'El Label PN seleccionado no tiene un formato activo en sku_serial_formats.');
                    }
                }
            }

            $jobNumber = (string) $this->input('job_number');
            if ($jobNumber !== '') {
                $job = $this->oracleJobLookup()->findByJobNumber($jobNumber);

                if (!$job) {
                    $validator->errors()->add('job_number', 'El Job no existe en Oracle Jobs.');
                    return;
                }

                if (!$this->oracleJobLookup()->isPackagingJob($job)) {
                    $validator->errors()->add('job_number', 'El Job debe pertenecer a Empaque (assembly 018/055/001).');
                }
            }
        });
    }

    private function oracleJobLookup(): OracleJobLookupService
    {
        if (!$this->oracleJobLookup) {
            $this->oracleJobLookup = app(OracleJobLookupService::class);
        }

        return $this->oracleJobLookup;
    }
}