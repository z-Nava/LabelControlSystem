<?php

namespace App\Http\Requests\Masters;

use App\Models\MasterModelMapping;
use App\Models\OracleJob;
use App\Models\ProductionLine;
use App\Services\Masters\MasterRequestLineValidationService;
use App\Services\Oracle\OracleJobService;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class StoreMasterRequestRequest extends FormRequest
{
    public const ACTION_SAVE = 'save';

    public const ACTION_SAVE_AND_PRINT = 'save_and_print';

    private ?OracleJobService $oracleJobService = null;

    private ?MasterRequestLineValidationService $lineValidationService = null;

    private const NO_HTML_PATTERN = '/<[^>]*>/';

    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $productionContextPresence = $this->isKioskRequest() ? 'required' : 'nullable';
        $lineRules = $this->isKioskRequest()
            ? ['required', 'integer', 'exists:production_lines,id']
            : ['prohibited'];
        $submissionActionRules = $this->isKioskRequest()
            ? ['prohibited']
            : ['required', Rule::in([self::ACTION_SAVE, self::ACTION_SAVE_AND_PRINT])];

        return [
            'submission_action' => $submissionActionRules,
            'request_date' => [$productionContextPresence, 'date', 'before_or_equal:today'],
            'week' => ['required', 'integer', 'min:1', 'max:53'],
            'line_id' => $lineRules,
            'shift_id' => [$productionContextPresence, 'integer', 'exists:shifts,id'],
            'leader_name' => [$productionContextPresence, 'string', 'min:3', 'max:120', 'regex:/^[\pL\s\-.\x27"]+$/u'],

            'po_number' => ['nullable', 'string', 'max:80', 'regex:/^[A-Za-z0-9\-\/_\s]+$/', 'not_regex:'.self::NO_HTML_PATTERN],
            'job_assembly' => [
                Rule::requiredIf(function (): bool {
                    return $this->string('request_type')->toString() !== 'assembly_packaging';
                }),
                'nullable',
                'string',
                'max:40',
                'regex:/^[0-9A-Za-z\-]+$/',
                'not_regex:'.self::NO_HTML_PATTERN,
                function (string $attribute, mixed $value, \Closure $fail): void {
                    if (! is_string($value) || trim($value) === '') {
                        return;
                    }

                    $job = $this->findOracleJob($value);

                    if (! $job) {
                        $fail('El Job Ensamble no existe en Oracle Jobs.');

                        return;
                    }

                    if (! $this->isAssemblyJob($job)) {
                        $fail($this->oracleJobService()->classificationValidationMessage('assembly'));
                    }
                },
            ],
            'job_packaging' => [
                Rule::requiredIf(function (): bool {
                    return $this->string('request_type')->toString() === 'assembly_packaging';
                }),
                'nullable',
                'string',
                'max:40',
                'regex:/^[0-9A-Za-z\-]+$/',
                'not_regex:'.self::NO_HTML_PATTERN,
                'different:job_assembly',
                function (string $attribute, mixed $value, \Closure $fail): void {
                    if (! is_string($value) || trim($value) === '') {
                        return;
                    }

                    $job = $this->findOracleJob($value);

                    if (! $job) {
                        $fail('El Job Empaque no existe en Oracle Jobs.');

                        return;
                    }

                    if (! $this->isPackagingJob($job)) {
                        $fail($this->oracleJobService()->classificationValidationMessage('packaging'));
                    }
                },
            ],
            'destination' => ['nullable', 'string', 'max:80', 'regex:/^[A-Za-z0-9\-\/_\s]+$/', 'not_regex:'.self::NO_HTML_PATTERN],
            'local' => [
                'nullable',
                'string',
                'max:20',
                'regex:/^[A-Za-z0-9\-._]+$/',
                'not_regex:'.self::NO_HTML_PATTERN,
            ],
            'subinventory' => [
                'nullable',
                'string',
                'max:20',
                'regex:/^[A-Za-z0-9\-._]+$/',
                'not_regex:'.self::NO_HTML_PATTERN,
            ],

            'folios_from' => ['required', 'integer', 'min:1'],
            'folios_to' => ['required', 'integer', 'min:1', 'gte:folios_from'],
            'std_pack_qty' => ['nullable', 'integer', 'min:1'],

            'partial_folio' => ['nullable', 'integer', 'min:1', 'required_with:partial_qty'],
            'partial_qty' => ['nullable', 'integer', 'min:1', 'required_with:partial_folio'],

            'request_type' => [
                'required',
                Rule::in(MasterModelMapping::REQUEST_TYPES),
                function (string $attribute, mixed $value, \Closure $fail): void {
                    if (! is_string($value) || ! in_array($value, MasterModelMapping::REQUEST_TYPES, true)) {
                        return;
                    }

                    if (! $this->isKioskRequest()) {
                        return;
                    }

                    $lineType = ProductionLine::query()
                        ->whereKey($this->input('line_id'))
                        ->value('line_type');

                    if ($lineType && ! MasterModelMapping::isAllowedForLineType($value, (string) $lineType)) {
                        $fail('El tipo de hoja master no corresponde al tipo de línea seleccionado.');
                    }
                },
            ],
            'kind' => ['required', 'in:new,reposition'],

            'notes' => ['nullable', 'string', 'max:1000', 'not_regex:'.self::NO_HTML_PATTERN],
        ];
    }

    protected function prepareForValidation(): void
    {
        $local = $this->cleanInput($this->input('local', ''));
        $subinventory = $this->cleanInput($this->input('subinventory', ''));

        $normalized = [
            'week' => $this->isKioskRequest() ? $this->input('week') : now()->weekOfYear,
            'leader_name' => $this->cleanInput($this->input('leader_name', '')),
            'po_number' => $this->cleanInput($this->input('po_number', '')),
            'job_assembly' => $this->cleanInput($this->input('job_assembly', '')),
            'job_packaging' => $this->cleanInput($this->input('job_packaging', '')),
            'destination' => $this->cleanInput($this->input('destination', '')),
            'local' => $local !== null ? strtoupper($local) : null,
            'subinventory' => $subinventory !== null ? strtoupper($subinventory) : null,
            'notes' => $this->cleanInput($this->input('notes', '')),
        ];

        if (! $this->isKioskRequest()) {
            $normalized['submission_action'] = $this->input('submission_action', self::ACTION_SAVE);
        }

        $this->merge($normalized);
    }

    /**
     * @return array<int, callable(Validator): void>
     */
    public function after(): array
    {
        return [
            function (Validator $validator): void {
                if (! $this->isKioskRequest()) {
                    return;
                }

                foreach (['line_id', 'request_type', 'job_assembly', 'job_packaging'] as $field) {
                    if ($validator->errors()->has($field)) {
                        return;
                    }
                }

                $selectedLine = ProductionLine::query()->find($this->integer('line_id'));

                if (! $selectedLine) {
                    return;
                }

                $assemblyJob = $this->filled('job_assembly')
                    ? $this->findOracleJob($this->string('job_assembly')->toString())
                    : null;
                $packagingJob = $this->filled('job_packaging')
                    ? $this->findOracleJob($this->string('job_packaging')->toString())
                    : null;

                $message = $this->lineValidationService()->validationError(
                    $selectedLine,
                    $this->string('request_type')->toString(),
                    $assemblyJob,
                    $packagingJob,
                );

                if ($message) {
                    $validator->errors()->add('line_id', $message);
                }
            },
        ];
    }

    private function cleanInput(mixed $value): ?string
    {
        if (! is_scalar($value)) {
            return null;
        }

        $cleaned = trim(strip_tags((string) $value));

        return $cleaned === '' ? null : $cleaned;
    }

    private function isKioskRequest(): bool
    {
        return $this->routeIs('kiosk.master_requests.store');
    }

    private function findOracleJob(string $jobNumber): ?OracleJob
    {
        return $this->oracleJobService()->findByJobNumber($jobNumber);
    }

    private function isAssemblyJob(OracleJob $job): bool
    {
        return $this->oracleJobService()->isAssemblyJob($job);
    }

    private function isPackagingJob(OracleJob $job): bool
    {
        return $this->oracleJobService()->isPackagingJob($job);
    }

    private function oracleJobService(): OracleJobService
    {
        if (! $this->oracleJobService) {
            $this->oracleJobService = app(OracleJobService::class);
        }

        return $this->oracleJobService;
    }

    private function lineValidationService(): MasterRequestLineValidationService
    {
        if (! $this->lineValidationService) {
            $this->lineValidationService = app(MasterRequestLineValidationService::class);
        }

        return $this->lineValidationService;
    }
}
