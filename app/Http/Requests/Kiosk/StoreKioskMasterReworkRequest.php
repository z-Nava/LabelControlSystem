<?php

namespace App\Http\Requests\Kiosk;

use App\Http\Requests\Masters\StoreMasterReworkRequest;

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
}
