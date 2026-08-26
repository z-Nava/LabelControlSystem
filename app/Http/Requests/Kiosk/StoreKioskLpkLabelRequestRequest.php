<?php

namespace App\Http\Requests\Kiosk;

use App\Models\LabelRequestLpkLabelGroup;
use App\Services\Labels\LabelRequestJobAvailabilityService;
use App\Services\Labels\LpkJobReservationCalculator;
use App\Services\Oracle\OracleJobService;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class StoreKioskLpkLabelRequestRequest extends FormRequest
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
        return [
            'request_date' => ['required', 'date', 'before_or_equal:today'],
            'week' => ['required', 'integer', 'min:1', 'max:53'],
            'line_id' => ['required', 'integer', 'exists:production_lines,id'],
            'shift_id' => ['required', 'integer', 'exists:shifts,id'],
            'leader_name' => ['required', 'string', 'min:3', 'max:120', 'regex:/^[\pL\s\-.\x27"]+$/u'],
            'lpk_label_groups' => ['present', 'array', 'max:50'],
            'lpk_label_groups.*.label_type' => ['required', Rule::in(LabelRequestLpkLabelGroup::TYPES)],
            'lpk_label_groups.*.part_number' => ['required', 'string', 'max:80'],
            'lpk_label_groups.*.items' => ['required', 'array', 'min:1', 'max:100'],
            'lpk_label_groups.*.items.*.job_number' => ['required', 'string', 'max:40', 'regex:/^[0-9A-Za-z\-]+$/'],
            'lpk_label_groups.*.items.*.model' => ['nullable', 'string', 'max:80'],
            'lpk_label_groups.*.items.*.quantity' => ['required', 'integer', 'min:1', 'max:100000'],
            'lpk_shipping_groups' => ['present', 'array', 'max:50'],
            'lpk_shipping_groups.*.part_number' => ['required', 'string', 'max:80'],
            'lpk_shipping_groups.*.quantity' => ['required', 'integer', 'min:1', 'max:100000'],
            'lpk_shipping_groups.*.po_number' => ['nullable', 'string', 'max:80'],
            'lpk_shipping_groups.*.destination' => ['nullable', 'string', 'max:80'],
            'lpk_shipping_groups.*.items' => ['required', 'array', 'min:1', 'max:100'],
            'lpk_shipping_groups.*.items.*.job_number' => ['required', 'string', 'max:40', 'regex:/^[0-9A-Za-z\-]+$/'],
            'lpk_shipping_groups.*.items.*.model' => ['nullable', 'string', 'max:80'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'lpk_label_groups' => $this->normalizeLabelGroups($this->input('lpk_label_groups', [])),
            'lpk_shipping_groups' => $this->normalizeShippingGroups($this->input('lpk_shipping_groups', [])),
        ]);
    }

    public function attributes(): array
    {
        return [
            'lpk_label_groups' => 'grupos Serial, Rating o Inner',
            'lpk_label_groups.*.label_type' => 'tipo de etiqueta',
            'lpk_label_groups.*.part_number' => 'NP de etiqueta',
            'lpk_label_groups.*.items' => 'modelos y Jobs del NP',
            'lpk_label_groups.*.items.*.job_number' => 'Job',
            'lpk_label_groups.*.items.*.model' => 'modelo',
            'lpk_label_groups.*.items.*.quantity' => 'cantidad',
            'lpk_shipping_groups' => 'grupos Shipping',
            'lpk_shipping_groups.*.part_number' => 'NP de Shipping',
            'lpk_shipping_groups.*.quantity' => 'cantidad de Shipping',
            'lpk_shipping_groups.*.po_number' => 'PO de Shipping',
            'lpk_shipping_groups.*.destination' => 'destino de Shipping',
            'lpk_shipping_groups.*.items' => 'modelos y Jobs de Shipping',
            'lpk_shipping_groups.*.items.*.job_number' => 'Job de Shipping',
            'lpk_shipping_groups.*.items.*.model' => 'modelo de Shipping',
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            $this->validateAtLeastOneGroup($validator);
            $this->validateDuplicateGroupsAndItems($validator);
            $this->validateJobsAndAvailability($validator);
        });
    }

    private function validateAtLeastOneGroup(Validator $validator): void
    {
        if (
            count($this->input('lpk_label_groups', [])) > 0
            || count($this->input('lpk_shipping_groups', [])) > 0
        ) {
            return;
        }

        $validator->errors()->add(
            'lpk_label_groups',
            'Agrega al menos un grupo de etiquetas o un grupo Shipping.',
        );
    }

    private function validateDuplicateGroupsAndItems(Validator $validator): void
    {
        $seenGroups = [];

        foreach ($this->input('lpk_label_groups', []) as $groupIndex => $group) {
            $groupKey = ($group['label_type'] ?? '').'|'.($group['part_number'] ?? '');

            if (isset($seenGroups[$groupKey])) {
                $validator->errors()->add(
                    "lpk_label_groups.{$groupIndex}.part_number",
                    'Este tipo y NP ya existen en otro grupo; agrega sus modelos y Jobs al grupo existente.',
                );
            }

            $seenGroups[$groupKey] = true;
            $this->validateDuplicateItems(
                $validator,
                $group['items'] ?? [],
                "lpk_label_groups.{$groupIndex}.items",
            );
        }

        foreach ($this->input('lpk_shipping_groups', []) as $groupIndex => $group) {
            $this->validateDuplicateItems(
                $validator,
                $group['items'] ?? [],
                "lpk_shipping_groups.{$groupIndex}.items",
            );
        }
    }

    /**
     * @param  array<int, array<string, mixed>>  $items
     */
    private function validateDuplicateItems(Validator $validator, array $items, string $path): void
    {
        $seenItems = [];

        foreach ($items as $itemIndex => $item) {
            $itemKey = ($item['job_number'] ?? '').'|'.($item['model'] ?? '');

            if (isset($seenItems[$itemKey])) {
                $validator->errors()->add(
                    "{$path}.{$itemIndex}.job_number",
                    'Este Job y modelo ya están incluidos en el mismo NP.',
                );
            }

            $seenItems[$itemKey] = true;
        }
    }

    private function validateJobsAndAvailability(Validator $validator): void
    {
        $jobPaths = [];
        $reservedByJob = app(LpkJobReservationCalculator::class)->calculate(
            $this->input('lpk_label_groups', []),
        );
        $quantityPaths = [];

        foreach ($this->input('lpk_label_groups', []) as $groupIndex => $group) {
            foreach ($group['items'] ?? [] as $itemIndex => $item) {
                $jobNumber = (string) ($item['job_number'] ?? '');

                if (! $this->isValidJobNumberFormat($jobNumber)) {
                    continue;
                }

                $jobPaths[$jobNumber][] = "lpk_label_groups.{$groupIndex}.items.{$itemIndex}.job_number";
                $quantity = (int) ($item['quantity'] ?? 0);

                if ($quantity === $reservedByJob->get($jobNumber) && ! isset($quantityPaths[$jobNumber])) {
                    $quantityPaths[$jobNumber] = "lpk_label_groups.{$groupIndex}.items.{$itemIndex}.quantity";
                }
            }
        }

        foreach ($this->input('lpk_shipping_groups', []) as $groupIndex => $group) {
            foreach ($group['items'] ?? [] as $itemIndex => $item) {
                $jobNumber = (string) ($item['job_number'] ?? '');

                if ($this->isValidJobNumberFormat($jobNumber)) {
                    $jobPaths[$jobNumber][] = "lpk_shipping_groups.{$groupIndex}.items.{$itemIndex}.job_number";
                }
            }
        }

        if (count($jobPaths) > 200) {
            $validator->errors()->add(
                'lpk_label_groups',
                'Una requisición LPK admite hasta 200 Jobs distintos.',
            );

            return;
        }

        foreach ($jobPaths as $jobNumber => $paths) {
            $job = $this->oracleJobService()->findByJobNumber($jobNumber);

            if (! $job) {
                foreach ($paths as $path) {
                    $validator->errors()->add($path, 'El Job no existe en Oracle Jobs.');
                }

                continue;
            }

            if (! $this->oracleJobService()->isPackagingJob($job)) {
                foreach ($paths as $path) {
                    $validator->errors()->add(
                        $path,
                        $this->oracleJobService()->classificationValidationMessage('packaging'),
                    );
                }

                continue;
            }

            if (! $reservedByJob->has($jobNumber)) {
                continue;
            }

            $availability = app(LabelRequestJobAvailabilityService::class)->calculate($job);

            if ($reservedByJob->get($jobNumber) > $availability['available_quantity']) {
                $validator->errors()->add(
                    $quantityPaths[$jobNumber],
                    "La cantidad solicitada para el Job {$jobNumber} supera su disponibilidad ({$availability['available_quantity']}).",
                );
            }
        }
    }

    /**
     * @return array<int, array{label_type: string, part_number: string, items: array<int, array{job_number: string, model: ?string, quantity: mixed}>}>
     */
    private function normalizeLabelGroups(mixed $groups): array
    {
        if (! is_array($groups)) {
            return [];
        }

        return collect($groups)
            ->filter(fn ($group): bool => is_array($group))
            ->map(fn (array $group): array => [
                'label_type' => strtolower(trim((string) ($group['label_type'] ?? ''))),
                'part_number' => strtoupper(trim((string) ($group['part_number'] ?? ''))),
                'items' => $this->normalizeItems($group['items'] ?? [], includeQuantity: true),
            ])
            ->values()
            ->all();
    }

    /**
     * @return array<int, array{part_number: string, quantity: mixed, po_number: ?string, destination: ?string, items: array<int, array{job_number: string, model: ?string}>}>
     */
    private function normalizeShippingGroups(mixed $groups): array
    {
        if (! is_array($groups)) {
            return [];
        }

        return collect($groups)
            ->filter(fn ($group): bool => is_array($group))
            ->map(fn (array $group): array => [
                'part_number' => strtoupper(trim((string) ($group['part_number'] ?? ''))),
                'quantity' => $group['quantity'] ?? null,
                'po_number' => $this->nullableUppercase($group['po_number'] ?? null),
                'destination' => $this->nullableUppercase($group['destination'] ?? null),
                'items' => $this->normalizeItems($group['items'] ?? [], includeQuantity: false),
            ])
            ->values()
            ->all();
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function normalizeItems(mixed $items, bool $includeQuantity): array
    {
        if (! is_array($items)) {
            return [];
        }

        return collect($items)
            ->filter(fn ($item): bool => is_array($item))
            ->map(function (array $item) use ($includeQuantity): array {
                $normalized = [
                    'job_number' => strtoupper(trim((string) ($item['job_number'] ?? ''))),
                    'model' => $this->nullableUppercase($item['model'] ?? null),
                ];

                if ($includeQuantity) {
                    $normalized['quantity'] = $item['quantity'] ?? null;
                }

                return $normalized;
            })
            ->values()
            ->all();
    }

    private function nullableUppercase(mixed $value): ?string
    {
        $normalized = strtoupper(trim((string) $value));

        return $normalized !== '' ? $normalized : null;
    }

    private function isValidJobNumberFormat(string $jobNumber): bool
    {
        return $jobNumber !== ''
            && strlen($jobNumber) <= 40
            && preg_match('/^[0-9A-Z\-]+$/', $jobNumber) === 1;
    }

    private function oracleJobService(): OracleJobService
    {
        if ($this->oracleJobService instanceof OracleJobService) {
            return $this->oracleJobService;
        }

        return $this->oracleJobService = app(OracleJobService::class);
    }
}
