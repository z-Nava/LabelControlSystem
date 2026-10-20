<?php

namespace App\Services\Kiosk;

use App\Models\KioskOptionStatus;
use InvalidArgumentException;

class KioskOptionService
{
    public const AVAILABLE = 'available';

    public const UNAVAILABLE = 'unavailable';

    public const OPTIONS = [
        'master' => 'Requisición Master',
        'labels' => 'Requisición de etiquetas',
        'lpk' => 'Requisición de etiquetas LPK',
        'dummy' => 'Requisición Dummy QR',
        'oracle' => 'Consultar Job en Oracle',
        'receipts' => 'Mis comprobantes',
    ];

    public const STATUSES = [
        self::AVAILABLE => 'Disponible',
        self::UNAVAILABLE => 'No disponible',
    ];

    /** @return array<string, string> */
    public function all(): array
    {
        $saved = KioskOptionStatus::query()->pluck('status', 'option_key')->all();
        $statuses = [];

        foreach (array_keys(self::OPTIONS) as $key) {
            $statuses[$key] = $this->normalizeStatus($saved[$key] ?? null);
        }

        return $statuses;
    }

    public function status(string $option): string
    {
        $this->ensureKnownOption($option);

        $status = KioskOptionStatus::query()->whereKey($option)->value('status');

        return $this->normalizeStatus($status);
    }

    public function setStatus(string $option, string $status): void
    {
        $this->ensureKnownOption($option);

        if (! array_key_exists($status, self::STATUSES)) {
            throw new InvalidArgumentException("Unknown Kiosk status: {$status}");
        }

        if ($status === self::AVAILABLE) {
            KioskOptionStatus::query()->whereKey($option)->delete();

            return;
        }

        KioskOptionStatus::query()->updateOrCreate(
            ['option_key' => $option],
            ['status' => $status],
        );
    }

    private function normalizeStatus(?string $status): string
    {
        return $status === null || $status === self::AVAILABLE
            ? self::AVAILABLE
            : self::UNAVAILABLE;
    }

    private function ensureKnownOption(string $option): void
    {
        if (! array_key_exists($option, self::OPTIONS)) {
            throw new InvalidArgumentException("Unknown Kiosk option: {$option}");
        }
    }
}
