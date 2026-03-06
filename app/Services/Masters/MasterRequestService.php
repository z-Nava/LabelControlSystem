<?php

namespace App\Services\Masters;

use App\Models\MasterRequest;
use App\Models\MasterRequestFolio;
use App\Models\OracleJob;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class MasterRequestService
{
    public function create(array $data): MasterRequest
    {
        return DB::transaction(function () use ($data) {

            $foliosFrom = (int) ($data['folios_from'] ?? 0);
            $foliosTo   = (int) ($data['folios_to'] ?? 0);
            $hasPartialData = !empty($data['partial_folio']) && !empty($data['partial_qty']);

            if ($foliosFrom < 1 || $foliosTo < $foliosFrom) {
                throw ValidationException::withMessages([
                    'folios_from' => 'Rango de folios inválido.',
                ]);
            }

            if ($hasPartialData) {
                // El folio parcial siempre debe ser el consecutivo del último folio normal.
                $data['partial_folio'] = $foliosTo + 1;
            } else {
                $data['partial_folio'] = null;
                $data['partial_qty'] = null;
            }

            $mr = MasterRequest::create($data);

            // Folios normales
            for ($f = $foliosFrom; $f <= $foliosTo; $f++) {
                MasterRequestFolio::create([
                    'master_request_id' => $mr->id,
                    'folio_number' => $f,
                    'is_partial' => false,
                    'qty_for_folio' => $data['std_pack_qty'] ?? null,
                    'status' => 'pending',
                ]);
            }

            // Folio parcial (opcional)
           if ($hasPartialData) {
                MasterRequestFolio::create([
                    'master_request_id' => $mr->id,
                    'folio_number' => (int) $data['partial_folio'],
                    'is_partial' => true,
                    'qty_for_folio' => (int) $data['partial_qty'],
                    'status' => 'pending',
                ]);
            }

            return $mr->load(['line', 'shift', 'folios']);
        });
    }

    /**
     * Lookup: dado un job_number te regresa lo que ocupará el front.
     */
    public function lookupOracleJob(string $jobNumber): array
    {
        $jobNumber = trim($jobNumber);

        $job = OracleJob::query()
            ->where('job_number', $jobNumber)
            ->first();

        if (!$job) {
            return [
                'found' => false,
                'job_number' => $jobNumber,
            ];
        }

        return [
            'found' => true,
            'job_number' => $job->job_number,
            'line' => $job->line,
            'assembly' => $job->assembly,               // NP ENSAMBLE
            'part_description' => $job->part_description,
            'ttl_cust_po' => $job->ttl_cust_po,         // PO
            'ship_code' => $job->ship_code,             // DESTINO
            'bom_revision' => $job->bom_revision,       // REVISION (motores)
            'valid_for_assembly' => $this->isAssemblyJob($job),
            'valid_for_packaging' => $this->isPackagingJob($job),
        ];
    }
    
    private function isAssemblyJob(OracleJob $job): bool
    {
        $assembly = strtoupper(trim((string) $job->assembly));
        $line = strtoupper(trim((string) $job->line));

        return str_starts_with($assembly, '103')
            || str_starts_with($assembly, '130')
            || str_starts_with($line, 'MEXMI')
            || str_starts_with($line, 'MXM');
    }

    private function isPackagingJob(OracleJob $job): bool
    {
        $assembly = strtoupper(trim((string) $job->assembly));

        return str_starts_with($assembly, '018')
            || str_starts_with($assembly, '055')
            || str_starts_with($assembly, '001');
    }
}

