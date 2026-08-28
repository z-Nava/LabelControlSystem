<?php

namespace App\Http\Requests\Masters;

use App\Services\Masters\MasterRequestJobStateService;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class LookupOracleJobRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'job_number' => ['required', 'string', 'max:40'],
            'role' => ['required', Rule::in([
                MasterRequestJobStateService::ROLE_ASSEMBLY,
                MasterRequestJobStateService::ROLE_PACKAGING,
            ])],
            'counterpart_job_number' => ['nullable', 'string', 'max:40', 'regex:/^[0-9A-Za-z\-]+$/'],
        ];
    }
}
