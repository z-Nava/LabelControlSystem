<?php

namespace App\Http\Requests\Dummies;

use Illuminate\Foundation\Http\FormRequest;

class IndexDummyRequestRequest extends FormRequest
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
            'job_number' => ['nullable', 'string', 'max:40'],
            'request_type' => ['nullable', 'in:first_time,rework'],
        ];
    }
}
