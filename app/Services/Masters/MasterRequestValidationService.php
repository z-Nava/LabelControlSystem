<?php

namespace App\Services\Masters;

use App\Models\OracleJob;

interface MasterRequestValidationService
{
    public function validate(
        array $data,
        ?OracleJob $assemblyJob,
        ?OracleJob $packagingJob,
    ): void;

    public function validateResolvedModel(?string $model): void;
}
