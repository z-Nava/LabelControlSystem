<?php

namespace App\Http\Requests\Masters;

use App\Models\MasterModelMapping;
use App\Services\Oracle\OracleJobService;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreManualMasterRequestRequest extends FormRequest
{
    private const NO_HTML_PATTERN = '/<[^>]*>/';

    private ?OracleJobService $oracleJobService = null;

    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'submission_action' => ['required', Rule::in([
                StoreMasterRequestRequest::ACTION_SAVE,
                StoreMasterRequestRequest::ACTION_SAVE_AND_PRINT,
            ])],
            'job_assembly' => [
                Rule::requiredIf(fn (): bool => $this->string('request_type')->toString() !== MasterModelMapping::TYPE_ASSEMBLY_PACKAGING),
                'nullable',
                'string',
                'max:40',
                'regex:/^[0-9A-Za-z\-]+$/',
                'not_regex:'.self::NO_HTML_PATTERN,
                $this->validOracleJobFor('assembly'),
            ],
            'job_packaging' => [
                Rule::requiredIf(fn (): bool => $this->string('request_type')->toString() === MasterModelMapping::TYPE_ASSEMBLY_PACKAGING),
                'nullable',
                'string',
                'max:40',
                'regex:/^[0-9A-Za-z\-]+$/',
                'not_regex:'.self::NO_HTML_PATTERN,
                'different:job_assembly',
                $this->validOracleJobFor('packaging'),
            ],
            'request_type' => ['required', Rule::in(MasterModelMapping::REQUEST_TYPES)],
            'oracle_line' => ['required', 'string', 'max:40', 'regex:/^[A-Za-z0-9\-._\s]+$/', 'not_regex:'.self::NO_HTML_PATTERN],
            'po_number' => ['nullable', 'string', 'max:80', 'regex:/^[A-Za-z0-9\-\/_\s]+$/', 'not_regex:'.self::NO_HTML_PATTERN],
            'destination' => ['nullable', 'string', 'max:80', 'regex:/^[A-Za-z0-9\-\/_\s]+$/', 'not_regex:'.self::NO_HTML_PATTERN],
            'model' => ['required', 'string', 'max:120', 'not_regex:'.self::NO_HTML_PATTERN],
            'local' => ['nullable', 'string', 'max:20', 'regex:/^[A-Za-z0-9\-._\s]+$/', 'not_regex:'.self::NO_HTML_PATTERN],
            'subinventory' => ['nullable', 'string', 'max:20', 'regex:/^[A-Za-z0-9\-._\s]+$/', 'not_regex:'.self::NO_HTML_PATTERN],
            'folios_from' => ['required', 'integer', 'min:1'],
            'folios_to' => ['required', 'integer', 'min:1', 'gte:folios_from'],
            'std_pack_qty' => ['required', 'integer', 'min:1'],
            'partial_folio' => ['nullable', 'integer', 'min:1', 'required_with:partial_qty'],
            'partial_qty' => ['nullable', 'integer', 'min:1', 'required_with:partial_folio'],
            'kind' => ['required', Rule::in(['new', 'reposition'])],
            'manual_reason' => ['required', 'string', 'min:3', 'max:1000', 'not_regex:'.self::NO_HTML_PATTERN],
            'notes' => ['nullable', 'string', 'max:1000', 'not_regex:'.self::NO_HTML_PATTERN],
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'submission_action' => $this->input('submission_action', StoreMasterRequestRequest::ACTION_SAVE),
            'job_assembly' => $this->normalizeNullable($this->input('job_assembly')),
            'job_packaging' => $this->normalizeNullable($this->input('job_packaging')),
            'oracle_line' => $this->normalizeNullable($this->input('oracle_line')),
            'po_number' => $this->cleanInput($this->input('po_number')),
            'destination' => $this->cleanInput($this->input('destination')),
            'model' => $this->cleanInput($this->input('model')),
            'local' => $this->normalizeNullable($this->input('local')),
            'subinventory' => $this->normalizeNullable($this->input('subinventory')),
            'manual_reason' => $this->cleanInput($this->input('manual_reason')),
            'notes' => $this->cleanInput($this->input('notes')),
        ]);
    }

    private function validOracleJobFor(string $role): \Closure
    {
        return function (string $attribute, mixed $value, \Closure $fail) use ($role): void {
            if (! is_string($value) || trim($value) === '') {
                return;
            }

            $job = $this->oracleJobService()->findByJobNumber($value);

            if (! $job) {
                $fail($role === 'packaging'
                    ? 'El Job Empaque no existe en Oracle Jobs.'
                    : 'El Job Ensamble no existe en Oracle Jobs.');

                return;
            }

            $isValid = $role === 'packaging'
                ? $this->oracleJobService()->isPackagingJob($job)
                : $this->oracleJobService()->isAssemblyJob($job);

            if (! $isValid) {
                $fail($this->oracleJobService()->classificationValidationMessage($role));
            }
        };
    }

    private function oracleJobService(): OracleJobService
    {
        return $this->oracleJobService ??= app(OracleJobService::class);
    }

    private function normalizeNullable(mixed $value): ?string
    {
        $normalized = $this->cleanInput($value);

        return $normalized !== null ? strtoupper($normalized) : null;
    }

    private function cleanInput(mixed $value): ?string
    {
        if (! is_scalar($value)) {
            return null;
        }

        $cleaned = trim(strip_tags((string) $value));

        return $cleaned !== '' ? $cleaned : null;
    }
}
