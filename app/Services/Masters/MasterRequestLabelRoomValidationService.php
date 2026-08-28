<?php

namespace App\Services\Masters;

use App\Models\OracleJob;
use Illuminate\Validation\ValidationException;

class MasterRequestLabelRoomValidationService implements MasterRequestValidationService
{
    public function __construct(
        private readonly MasterRequestFolioValidationService $folioValidationService,
    ) {}

    public function validate(
        array $data,
        ?OracleJob $assemblyJob,
        ?OracleJob $packagingJob,
    ): void {
        $this->folioValidationService->validateNewRequest(
            $data,
            $assemblyJob,
            $packagingJob,
        );
    }

    public function validateResolvedModel(?string $model): void
    {
        if ($model !== null) {
            return;
        }

        throw ValidationException::withMessages([
            'model' => 'No se puede enviar la requisición: el assembly no tiene un modelo activo en Master Model Mapping.',
        ]);
    }
}
