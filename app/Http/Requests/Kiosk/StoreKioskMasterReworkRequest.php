<?php

namespace App\Http\Requests\Kiosk;

use App\Http\Requests\Masters\StoreMasterReworkRequest;
use App\Models\MasterRequest;
use App\Services\Oracle\OracleJobService;
use Illuminate\Validation\Validator;

class StoreKioskMasterReworkRequest extends StoreMasterReworkRequest
{
    public function authorize(): bool
    {
        return $this->attributes->get('kiosk_user') !== null;
    }

    public function rules(): array
    {
        return [
            ...parent::rules(),
            'rework_reason' => ['required', 'string', 'min:10', 'max:500', 'not_regex:/<[^>]*>/'],
        ];
    }

    /** @return array<int, callable(Validator): void> */
    public function after(): array
    {
        return [
            ...parent::after(),
            function (Validator $validator): void {
                if ($validator->errors()->hasAny(['po_number', 'destination'])) {
                    return;
                }

                $source = $this->route('master_request');
                if (! $source instanceof MasterRequest) {
                    return;
                }

                $poNumber = strtoupper(trim((string) $this->input('po_number')));
                $destination = strtoupper(trim((string) $this->input('destination')));

                if (
                    $poNumber === strtoupper(trim((string) $source->po_number))
                    && $destination === strtoupper(trim((string) $source->destination))
                ) {
                    return;
                }

                if ($poNumber === '') {
                    return;
                }

                if ($destination === '') {
                    $validator->errors()->add('destination', 'Captura el destino correspondiente a la PO.');

                    return;
                }

                $destinations = app(OracleJobService::class)->destinationsForPo($poNumber);

                if ($destinations !== [] && ! in_array($destination, $destinations, true)) {
                    $validator->errors()->add(
                        'destination',
                        'El destino no corresponde a la PO en los Jobs de Oracle. Selecciona uno de los destinos encontrados.'
                    );
                }
            },
        ];
    }
}
