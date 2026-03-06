<?php

namespace App\Http\Requests\Labels;

use Illuminate\Foundation\Http\FormRequest;

class IndexLabelRequestRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'date_from' => ['nullable', 'date'],
            'date_to' => ['nullable', 'date', 'after_or_equal:date_from'],
            'line_id' => ['nullable', 'integer', 'exists:production_lines,id'],
            'shift_id' => ['nullable', 'integer', 'exists:shifts,id'],
            'status' => ['nullable', 'in:requested,in_progress,completed,cancelled'],
            'sku_np' => ['nullable', 'string', 'max:80'],
        ];
    }
}
