<?php

namespace App\Http\Requests\Oracle;

use Illuminate\Foundation\Http\FormRequest;

class ImportOracleJobsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // lo controlamos por middleware de ruta
    }

    public function rules(): array
    {
        return [
            'file' => ['required', 'file', 'mimes:xlsx,xls', 'max:20480'], // 20MB
        ];
    }
}
