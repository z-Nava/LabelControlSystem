<?php

namespace App\Services\Labels;

use App\Models\SerialRange;
use App\Models\SerialWeek;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class LabelFolioService
{
    public const MAX_FOLIO = 4294967295;

    public function findWeek(string $part, string $family, int $year, int $week, bool $lock = false): ?SerialWeek
    {
        return SerialWeek::where('label_part_number', $part)->where('folio_family', $family)
            ->where('year', $year)->where('week', $week)->when($lock, fn ($q) => $q->lockForUpdate())->first();
    }

    public function nextNumber(string $part, string $family, int $year, int $week): int
    {
        $record = $this->findWeek($part, $family, $year, $week);
        if ($record) {
            return $this->highWater($record) + 1;
        }

        $this->assertCanOpenWeek($part, $family, $year, $week);

        return 1;
    }

    public function lockWeek(string $part, string $family, int $year, int $week): SerialWeek
    {
        if (! $this->findWeek($part, $family, $year, $week)) {
            $this->assertCanOpenWeek($part, $family, $year, $week);
            DB::table('serial_weeks')->insertOrIgnore([
                'label_part_number' => $part, 'folio_family' => $family, 'year' => $year, 'week' => $week,
                'serial_standard' => null, 'last_serial_number' => 0, 'opening_serial_number' => 0,
                'created_at' => now(), 'updated_at' => now(),
            ]);
        }

        return $this->findWeek($part, $family, $year, $week, true);
    }

    private function assertCanOpenWeek(string $part, string $family, int $year, int $week): void
    {
        $legacy = SerialWeek::where('label_part_number', $part)->whereNull('folio_family')
            ->where('year', $year)->where('week', $week)->exists();
        $knownEarlier = SerialWeek::where('label_part_number', $part)->where('folio_family', $family)
            ->where(fn ($q) => $q->where('year', '<', $year)->orWhere(fn ($q) => $q->where('year', $year)->where('week', '<', $week)))->exists();

        if ($legacy || ! $knownEarlier) {
            $otherFamilies = SerialWeek::where('label_part_number', $part)->whereNotNull('folio_family')
                ->where('year', $year)->where('week', $week)->orderBy('folio_family')->pluck('folio_family');
            if ($otherFamilies->isNotEmpty()) {
                throw ValidationException::withMessages(['tasks' => "No existe un control para NP Rating {$part}, familia {$family}, año {$year}, semana {$week}. Para ese NP, año y semana ya hay controles con familia: {$otherFamilies->implode(', ')}. La familia se obtiene del SKU del ensamble de la Job. Revisa el control semanal: debe estar registrado con ese SKU exacto. El número de parte por sí solo no identifica el consecutivo."]);
            }

            throw ValidationException::withMessages(['tasks' => "Inicializa el control de NP Rating {$part}, familia {$family}, año {$year}, semana {$week}, con el último folio verificado en Excel. Captura 0 en Último folio utilizado únicamente si la semana no tiene impresiones; Familia de folios es el SKU completo del ensamble de la Job."]);
        }
    }

    public function highWater(SerialWeek $week): int
    {
        return max((int) $week->last_serial_number,
            (int) SerialRange::where('serial_week_id', $week->id)->max('range_end'),
            (int) DB::table('serial_units')->where('serial_week_id', $week->id)->max('serial_number'));
    }

    public function initialize(array $data, User $user): SerialWeek
    {
        return DB::transaction(function () use ($data, $user) {
            $part = strtoupper(trim($data['label_part_number']));
            $family = strtoupper(trim($data['folio_family']));
            $year = (int) $data['year'];
            $week = (int) $data['week'];
            // A unique insert followed by the row lock also serializes simultaneous initialization.
            DB::table('serial_weeks')->insertOrIgnore([
                'label_part_number' => $part, 'folio_family' => $family, 'year' => $year, 'week' => $week,
                'serial_standard' => null, 'last_serial_number' => 0, 'opening_serial_number' => 0,
                'created_at' => now(), 'updated_at' => now(),
            ]);
            $record = $this->findWeek($part, $family, $year, $week, true);
            $legacyIds = SerialWeek::where('label_part_number', $part)->whereNull('folio_family')
                ->where('year', $year)->where('week', $week)->pluck('id');
            $minimum = max($this->highWater($record),
                (int) SerialWeek::whereIn('id', $legacyIds)->max('last_serial_number'),
                (int) SerialRange::whereIn('serial_week_id', $legacyIds)->max('range_end'),
                (int) DB::table('serial_units')->whereIn('serial_week_id', $legacyIds)->max('serial_number'));
            if ((int) $data['last_serial_number'] < $minimum) {
                throw ValidationException::withMessages(['last_serial_number' => "El último folio no puede ser menor que el registrado ({$minimum})."]);
            }
            $before = $record->last_serial_number;
            $record->update([
                'last_serial_number' => $data['last_serial_number'],
                'opening_serial_number' => $record->initialized_by_user_id ? $record->opening_serial_number : $data['last_serial_number'],
                'opening_notes' => $data['opening_notes'], 'initialized_by_user_id' => $user->id,
            ]);
            DB::table('label_administration_events')->insert([
                'user_id' => $user->id, 'action' => 'initialize_week',
                'details' => json_encode(['serial_week_id' => $record->id, 'before' => $before, 'after' => $record->last_serial_number, 'reason' => $data['opening_notes']]),
                'created_at' => now(),
            ]);

            return $record;
        }, 3);
    }
}
