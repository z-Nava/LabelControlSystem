<?php

namespace App\Http\Requests\Masters;

use Illuminate\Foundation\Http\FormRequest;

class StoreMasterPrintBatchRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'batch_type' => ['required', 'in:print,reprint,rework'],
            'reason' => ['nullable', 'string', 'max:500'],
            'copies' => ['required', 'integer', 'min:1', 'max:20'],
            'folio_ids' => ['required', 'array', 'min:1'],
            'folio_ids.*' => ['integer', 'exists:master_request_folios,id'],
        ];
    }
}