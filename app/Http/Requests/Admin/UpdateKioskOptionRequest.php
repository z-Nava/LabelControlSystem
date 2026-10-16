<?php

namespace App\Http\Requests\Admin;

use App\Services\Kiosk\KioskOptionService;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateKioskOptionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'status' => ['required', 'string', Rule::in(array_keys(KioskOptionService::STATUSES))],
        ];
    }
}
