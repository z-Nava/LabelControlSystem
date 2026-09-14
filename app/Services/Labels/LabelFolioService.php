<?php

namespace App\Services\Labels;

use App\Models\SerialPeriod;
use App\Models\SerialRange;
use App\Models\User;
use App\Support\SerialPeriods;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class LabelFolioService
{
    public const MAX_FOLIO = 4294967295;

    public function findPeriod(
        string $part,
        string $market,
        string $periodType,
        int $year,
        int $periodNumber,
        bool $lock = false,
    ): ?SerialPeriod {
        return SerialPeriod::query()
            ->where('label_part_number', $this->normalize($part))
            ->where('serial_standard', strtoupper(trim($market)))
            ->where('period_type', $periodType)
            ->where('year', $year)
            ->where('period_number', $periodNumber)
            ->when($lock, fn ($query) => $query->lockForUpdate())
            ->first();
    }

    public function nextNumber(string $part, string $market, string $periodType, int $year, int $periodNumber): int
    {
        $this->assertValidPeriodNumber($periodType, $periodNumber);
        $record = $this->findPeriod($part, $market, $periodType, $year, $periodNumber);
        if ($record) {
            return $this->highWater($record) + 1;
        }

        $this->assertCanOpenPeriod($part, $market, $periodType, $year, $periodNumber);

        return 1;
    }

    public function lockPeriod(
        string $part,
        string $market,
        string $periodType,
        int $year,
        int $periodNumber,
        int $operationalWeek,
    ): SerialPeriod {
        $this->assertValidPeriodNumber($periodType, $periodNumber);
        if (! $this->findPeriod($part, $market, $periodType, $year, $periodNumber)) {
            $this->assertCanOpenPeriod($part, $market, $periodType, $year, $periodNumber);
            $this->insertPeriod($part, $market, $periodType, $year, $periodNumber, $operationalWeek);
        }

        return $this->findPeriod($part, $market, $periodType, $year, $periodNumber, true);
    }

    public function highWater(SerialPeriod $period): int
    {
        return max(
            (int) $period->last_serial_number,
            (int) SerialRange::query()->where('serial_week_id', $period->id)->max('range_end'),
            (int) DB::table('serial_units')->where('serial_week_id', $period->id)->max('serial_number'),
        );
    }

    public function initialize(array $data, User $user): SerialPeriod
    {
        return DB::transaction(function () use ($data, $user): SerialPeriod {
            $part = $this->normalize($data['label_part_number']);
            $market = strtoupper(trim((string) $data['serial_standard']));
            $periodType = SerialPeriods::forMarket($market);
            $year = (int) $data['year'];
            $periodNumber = (int) $data['period_number'];
            $this->assertValidPeriodNumber($periodType, $periodNumber);
            $operationalWeek = (int) ($data['control_week'] ?? now(config('app.display_timezone'))->isoWeek());

            $this->insertPeriod($part, $market, $periodType, $year, $periodNumber, $operationalWeek);
            $record = $this->findPeriod($part, $market, $periodType, $year, $periodNumber, true);
            $minimum = $this->minimumVerifiedFolio($record);

            if ((int) $data['last_serial_number'] < $minimum) {
                throw ValidationException::withMessages([
                    'last_serial_number' => "El último folio no puede ser menor que el registrado ({$minimum}).",
                ]);
            }

            $before = (int) $record->last_serial_number;
            $record->update([
                'last_serial_number' => (int) $data['last_serial_number'],
                'opening_serial_number' => $record->initialized_by_user_id
                    ? $record->opening_serial_number
                    : (int) $data['last_serial_number'],
                'opening_notes' => $data['opening_notes'],
                'initialized_by_user_id' => $user->id,
            ]);

            DB::table('label_administration_events')->insert([
                'user_id' => $user->id,
                'action' => 'initialize_period',
                'details' => json_encode([
                    'serial_period_id' => $record->id,
                    'market' => $market,
                    'period_type' => $periodType,
                    'year' => $year,
                    'period_number' => $periodNumber,
                    'before' => $before,
                    'after' => $record->last_serial_number,
                    'reason' => $data['opening_notes'],
                ]),
                'created_at' => now(),
            ]);

            return $record;
        }, 3);
    }

    private function assertCanOpenPeriod(string $part, string $market, string $periodType, int $year, int $periodNumber): void
    {
        $part = $this->normalize($part);
        $market = strtoupper(trim($market));
        $knownEarlier = SerialPeriod::query()
            ->where('label_part_number', $part)
            ->where('serial_standard', $market)
            ->where('period_type', $periodType)
            ->where(fn ($query) => $query
                ->where('year', '<', $year)
                ->orWhere(fn ($nested) => $nested->where('year', $year)->where('period_number', '<', $periodNumber)))
            ->exists();

        $hasLegacyControl = $periodType === SerialPeriods::WEEK && SerialPeriod::query()
            ->where('label_part_number', $part)
            ->whereNull('period_type')
            ->where(fn ($query) => $query
                ->where('serial_standard', $market)
                ->orWhereNull('serial_standard'))
            ->where('year', $year)
            ->where('week', $periodNumber)
            ->exists();

        if (! $knownEarlier || $hasLegacyControl) {
            $period = mb_strtolower(SerialPeriods::describe($periodType, $periodNumber));
            throw ValidationException::withMessages([
                'tasks' => "Inicializa el control de NP Rating {$part}, mercado {$market}, año {$year}, {$period}, con el último folio verificado. Captura 0 únicamente si ese periodo no tiene impresiones.",
            ]);
        }
    }

    private function insertPeriod(string $part, string $market, string $periodType, int $year, int $periodNumber, int $operationalWeek): void
    {
        DB::table('serial_weeks')->insertOrIgnore([
            'label_part_number' => $this->normalize($part),
            'folio_family' => null,
            'serial_standard' => strtoupper(trim($market)),
            'period_type' => $periodType,
            'period_number' => $periodNumber,
            'year' => $year,
            'week' => $operationalWeek,
            'last_serial_number' => 0,
            'opening_serial_number' => 0,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function minimumVerifiedFolio(SerialPeriod $record): int
    {
        $minimum = $this->highWater($record);

        if ($record->period_type !== SerialPeriods::WEEK) {
            return $minimum;
        }

        $legacyIds = SerialPeriod::query()
            ->where('label_part_number', $record->label_part_number)
            ->whereNull('period_type')
            ->where(fn ($query) => $query
                ->where('serial_standard', $record->serial_standard)
                ->orWhereNull('serial_standard'))
            ->where('year', $record->year)
            ->where('week', $record->period_number)
            ->pluck('id');

        return max(
            $minimum,
            (int) SerialPeriod::query()->whereIn('id', $legacyIds)->max('last_serial_number'),
            (int) SerialRange::query()->whereIn('serial_week_id', $legacyIds)->max('range_end'),
            (int) DB::table('serial_units')->whereIn('serial_week_id', $legacyIds)->max('serial_number'),
        );
    }

    private function normalize(mixed $value): string
    {
        return strtoupper(trim((string) $value));
    }

    private function assertValidPeriodNumber(string $periodType, int $periodNumber): void
    {
        if ($periodNumber < 1 || $periodNumber > SerialPeriods::maximum($periodType)) {
            throw ValidationException::withMessages([
                'period_number' => 'El número de periodo no corresponde al tipo de control seleccionado.',
            ]);
        }
    }
}
