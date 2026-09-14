<?php

namespace App\Support;

use InvalidArgumentException;

final class SerialPeriods
{
    public const WEEK = 'week';

    public const MONTH = 'month';

    /**
     * @return array<int, string>
     */
    public static function all(): array
    {
        return [self::WEEK, self::MONTH];
    }

    public static function forMarket(string $market): string
    {
        return strtoupper(trim($market)) === SerialStandards::UL ? self::WEEK : self::MONTH;
    }

    public static function maximum(string $type): int
    {
        return match ($type) {
            self::WEEK => 53,
            self::MONTH => 12,
            default => throw new InvalidArgumentException("Tipo de periodo no soportado: {$type}"),
        };
    }

    public static function label(string $type): string
    {
        return match ($type) {
            self::WEEK => 'Semana',
            self::MONTH => 'Mes',
            default => ucfirst($type),
        };
    }

    public static function describe(string $type, int $number): string
    {
        if ($type !== self::MONTH) {
            return self::label($type).' '.$number;
        }

        $months = [
            1 => 'Enero', 2 => 'Febrero', 3 => 'Marzo', 4 => 'Abril',
            5 => 'Mayo', 6 => 'Junio', 7 => 'Julio', 8 => 'Agosto',
            9 => 'Septiembre', 10 => 'Octubre', 11 => 'Noviembre', 12 => 'Diciembre',
        ];

        return $months[$number] ?? 'Mes '.$number;
    }
}
