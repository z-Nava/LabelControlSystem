<?php

namespace App\Http\Requests\Masters;

use Illuminate\Foundation\Http\FormRequest;

class StoreMasterRequestRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'request_date' => ['required', 'date'],
            'week' => ['required', 'integer', 'min:1', 'max:53'],
            'line_id' => ['required', 'exists:production_lines,id'],
            'shift_id' => ['required', 'exists:shifts,id'],
            'leader_name' => ['required', 'string', 'max:120'],

            'po_number' => ['nullable', 'string', 'max:80'],
            'job_assembly' => ['nullable', 'string', 'max:40'],
            'job_packaging' => ['nullable', 'string', 'max:40'],
            'destination' => ['nullable', 'string', 'max:80'],

            'folios_from' => ['required', 'integer', 'min:1'],
            'folios_to' => ['required', 'integer', 'min:1'],
            'std_pack_qty' => ['nullable', 'integer', 'min:1'],

            'partial_folio' => ['nullable', 'integer', 'min:1'],
            'partial_qty' => ['nullable', 'integer', 'min:1'],

            'request_type' => ['required', 'string', 'max:40'],
            'kind' => ['required', 'in:new,reposition'],

            'notes' => ['nullable', 'string'],
        ];
    }
}