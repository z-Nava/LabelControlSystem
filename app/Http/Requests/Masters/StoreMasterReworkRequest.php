<?php

namespace App\Http\Requests\Masters;

use App\Models\MasterModelMapping;
use App\Models\MasterRequest;
use App\Services\Oracle\OracleJobService;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class StoreMasterReworkRequest extends FormRequest
{
    private const NO_HTML_PATTERN = '/<[^>]*>/';

    private ?OracleJobService $oracleJobService = null;

    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            'rework_reason' => ['required', 'string', 'max:500', 'not_regex:'.self::NO_HTML_PATTERN],
            'job_assembly' => [
                Rule::requiredIf(fn (): bool => $this->string('request_type')->toString() !== MasterModelMapping::TYPE_ASSEMBLY_PACKAGING),
                'nullable',
                'string',
                'max:40',
                'regex:/^[0-9A-Za-z\-]+$/',
                'different:job_packaging',
                $this->validJobRoleRule('assembly'),
            ],
            'job_packaging' => [
                Rule::requiredIf(fn (): bool => $this->string('request_type')->toString() === MasterModelMapping::TYPE_ASSEMBLY_PACKAGING),
                'nullable',
                'string',
                'max:40',
                'regex:/^[0-9A-Za-z\-]+$/',
                'different:job_assembly',
                $this->validJobRoleRule('packaging'),
            ],
            'request_type' => ['required', Rule::in(MasterModelMapping::REQUEST_TYPES)],
            'line_id' => [
                'required',
                'integer',
                Rule::exists('production_lines', 'id')->where('active', true),
            ],
            'local' => ['nullable', 'string', 'max:20', 'regex:/^[A-Za-z0-9\-._]+$/'],
            'subinventory' => ['nullable', 'string', 'max:20', 'regex:/^[A-Za-z0-9\-._]+$/'],
            'model' => ['nullable', 'string', 'max:120', 'not_regex:'.self::NO_HTML_PATTERN],
            'std_pack_qty' => ['nullable', 'integer', 'min:1'],
            'selected_folio_numbers' => ['nullable', 'array'],
            'selected_folio_numbers.*' => ['integer', 'min:1', 'distinct'],
            'additional_folios_count' => ['required', 'integer', 'min:0', 'max:500'],
            'partial_folio' => ['nullable', 'integer', 'min:1', 'required_with:partial_qty'],
            'partial_qty' => ['nullable', 'integer', 'min:1', 'required_with:partial_folio'],
            'notes' => ['nullable', 'string', 'max:1000', 'not_regex:'.self::NO_HTML_PATTERN],
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'job_assembly' => $this->normalize($this->input('job_assembly')),
            'job_packaging' => $this->normalize($this->input('job_packaging')),
            'local' => $this->normalize($this->input('local')),
            'subinventory' => $this->normalize($this->input('subinventory')),
            'model' => $this->normalize($this->input('model')),
            'rework_reason' => $this->cleanText($this->input('rework_reason')),
            'notes' => $this->cleanText($this->input('notes')),
            'additional_folios_count' => $this->input('additional_folios_count', 0),
        ]);
    }

    /**
     * @return array<int, callable(Validator): void>
     */
    public function after(): array
    {
        return [
            function (Validator $validator): void {
                if ($validator->errors()->hasAny([
                    'selected_folio_numbers',
                    'selected_folio_numbers.*',
                    'additional_folios_count',
                    'partial_folio',
                    'partial_qty',
                ])) {
                    return;
                }

                $masterRequest = $this->route('master_request');

                if (! $masterRequest instanceof MasterRequest) {
                    return;
                }

                $availableFolios = $masterRequest->folios()
                    ->pluck('folio_number')
                    ->map(fn (mixed $folio): int => (int) $folio);
                $selectedFolios = collect($this->input('selected_folio_numbers', []))
                    ->map(fn (mixed $folio): int => (int) $folio)
                    ->unique()
                    ->values();
                $unknownFolios = $selectedFolios->diff($availableFolios);

                if ($unknownFolios->isNotEmpty()) {
                    $validator->errors()->add(
                        'selected_folio_numbers',
                        'Uno o más folios seleccionados no pertenecen a la requisición original.'
                    );

                    return;
                }

                $additionalCount = $this->integer('additional_folios_count');

                if ($selectedFolios->isEmpty() && $additionalCount === 0) {
                    $validator->errors()->add(
                        'selected_folio_numbers',
                        'Selecciona al menos un folio original o agrega un folio nuevo.'
                    );

                    return;
                }

                if (! $this->filled('partial_folio')) {
                    return;
                }

                $originalMaximum = (int) $availableFolios->max();
                $additionalFolios = $additionalCount > 0
                    ? range($originalMaximum + 1, $originalMaximum + $additionalCount)
                    : [];
                $finalFolios = $selectedFolios->merge($additionalFolios);

                if (! $finalFolios->contains($this->integer('partial_folio'))) {
                    $validator->errors()->add(
                        'partial_folio',
                        'El folio parcial debe formar parte de los folios seleccionados o agregados.'
                    );
                }
            },
        ];
    }

    private function validJobRoleRule(string $role): \Closure
    {
        return function (string $attribute, mixed $value, \Closure $fail) use ($role): void {
            if (! is_string($value) || trim($value) === '') {
                return;
            }

            $job = $this->oracleJobService()->findByJobNumber($value);

            if (! $job) {
                $fail($role === 'assembly'
                    ? 'El Job Ensamble no existe en Oracle Jobs.'
                    : 'El Job Empaque no existe en Oracle Jobs.');

                return;
            }

            $isValid = $role === 'assembly'
                ? $this->oracleJobService()->isAssemblyJob($job)
                : $this->oracleJobService()->isPackagingJob($job);

            if (! $isValid) {
                $fail($role === 'assembly'
                    ? 'El Job Ensamble no corresponde a una operación válida de Ensamble.'
                    : 'El Job Empaque no corresponde a una operación válida de Empaque.');
            }
        };
    }

    private function oracleJobService(): OracleJobService
    {
        return $this->oracleJobService ??= app(OracleJobService::class);
    }

    private function normalize(mixed $value): ?string
    {
        $cleaned = $this->cleanText($value);

        return $cleaned !== null ? strtoupper($cleaned) : null;
    }

    private function cleanText(mixed $value): ?string
    {
        if (! is_scalar($value)) {
            return null;
        }

        $cleaned = trim(strip_tags((string) $value));

        return $cleaned !== '' ? $cleaned : null;
    }
}
