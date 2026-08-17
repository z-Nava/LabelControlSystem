<?php

namespace App\Http\Requests\Kiosk;

use App\Services\Labels\LabelRequestJobAvailabilityService;
use App\Services\Oracle\OracleJobService;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class StoreKioskLabelRequestRequest extends FormRequest
{
    private ?OracleJobService $oracleJobService = null;

    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        $requiresRatingPartNumber = fn (): bool => $this->boolean('include_rating');
        $requiresShippingQuantity = fn (): bool => $this->boolean('include_shipping');

        return [
            'request_date' => ['required', 'date', 'before_or_equal:today'],
            'week' => ['required', 'integer', 'min:1', 'max:53'],
            'line_id' => ['required', 'integer', 'exists:production_lines,id'],
            'shift_id' => ['required', 'integer', 'exists:shifts,id'],
            'leader_name' => ['required', 'string', 'min:3', 'max:120', 'regex:/^[\pL\s\-.\x27"]+$/u'],
            'job_number' => ['required', 'string', 'max:40', 'regex:/^[0-9A-Za-z\-]+$/'],
            'po_number' => ['nullable', 'string', 'max:80', 'regex:/^[A-Za-z0-9\-\/_\s]+$/'],
            'destination' => ['nullable', 'string', 'max:80', 'regex:/^[A-Za-z0-9\-\/_\s]+$/'],
            'model' => ['required', 'string', 'max:80'],
            'serial_part_number' => ['required', 'string', 'max:80'],
            'rating_part_numbers' => [Rule::requiredIf($requiresRatingPartNumber), 'array'],
            'rating_part_numbers.*' => ['required', 'string', 'max:80', 'distinct:ignore_case'],
            'quantity_requested' => ['required', 'integer', 'min:1', 'max:100000'],
            'shipping_quantity' => [Rule::requiredIf($requiresShippingQuantity), 'nullable', 'integer', 'min:1', 'max:100000'],
            'include_serial' => ['nullable', 'boolean'],
            'include_rating' => ['nullable', 'boolean'],
            'include_inner' => ['nullable', 'boolean'],
            'include_shipping' => ['nullable', 'boolean'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $includeRating = $this->boolean('include_rating');
        $includeShipping = $this->boolean('include_shipping');
        $ratingPartNumbers = $this->input('rating_part_numbers', []);

        if (! is_array($ratingPartNumbers)) {
            $ratingPartNumbers = [$ratingPartNumbers];
        }

        $ratingPartNumbers = array_values(array_filter(
            array_map(
                static fn ($partNumber): string => strtoupper(trim((string) $partNumber)),
                $ratingPartNumbers,
            ),
            static fn (string $partNumber): bool => $partNumber !== '',
        ));

        $this->merge([
            'include_serial' => $this->boolean('include_serial'),
            'include_rating' => $includeRating,
            'include_inner' => $this->boolean('include_inner'),
            'include_shipping' => $includeShipping,
            'serial_part_number' => strtoupper(trim((string) $this->input('serial_part_number'))),
            'rating_part_numbers' => $includeRating ? $ratingPartNumbers : [],
            'shipping_quantity' => $includeShipping && trim((string) $this->input('shipping_quantity')) !== ''
                ? $this->input('shipping_quantity')
                : null,
            'job_number' => strtoupper(trim((string) $this->input('job_number'))),
            'po_number' => strtoupper(trim((string) $this->input('po_number'))),
            'destination' => strtoupper(trim((string) $this->input('destination'))),
            'model' => strtoupper(trim((string) $this->input('model'))),
        ]);
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            $this->validateAtLeastOneLabelType($validator);
            $this->validateJobAndAvailability($validator);
        });
    }

    private function validateAtLeastOneLabelType(Validator $validator): void
    {
        if (
            $this->boolean('include_serial')
            || $this->boolean('include_rating')
            || $this->boolean('include_inner')
            || $this->boolean('include_shipping')
        ) {
            return;
        }

        $validator->errors()->add('include_serial', 'Debes seleccionar al menos un tipo de etiqueta.');
    }

    private function validateJobAndAvailability(Validator $validator): void
    {
        $jobNumber = (string) $this->input('job_number');

        if ($jobNumber === '') {
            return;
        }

        $jobService = $this->oracleJobService();
        $job = $jobService->findByJobNumber($jobNumber);

        if (! $job) {
            $validator->errors()->add('job_number', 'El Job no existe en Oracle Jobs.');

            return;
        }

        if (! $jobService->isPackagingJob($job)) {
            $validator->errors()->add('job_number', 'El Job debe pertenecer a Empaque (assembly 018/055/001/270).');

            return;
        }

        $availability = app(LabelRequestJobAvailabilityService::class)->calculate($job);
        $requestedQuantity = (int) $this->input('quantity_requested');

        if ($requestedQuantity > $availability['available_quantity']) {
            $validator->errors()->add(
                'quantity_requested',
                "La cantidad solicitada supera la disponibilidad del Job ({$availability['available_quantity']})."
            );
        }
    }

    private function oracleJobService(): OracleJobService
    {
        if ($this->oracleJobService instanceof OracleJobService) {
            return $this->oracleJobService;
        }

        return $this->oracleJobService = app(OracleJobService::class);
    }
}
