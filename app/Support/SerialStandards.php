<?php

namespace App\Support;

final class SerialStandards
{
    public const UL = 'UL';
    public const EMEA = 'EMEA';
    public const ANZ = 'ANZ';

    /**
     * @return array<int, string>
     */
    public static function all(): array
    {
        return [
            self::UL,
            self::EMEA,
            self::ANZ,
        ];
    }

    /**
     * Standards currently enabled for label request flow.
     *
     * @return array<int, string>
     */
    public static function requestFlow(): array
    {
        return self::all();
    }

    public static function normalize(?string $value, string $default = self::UL): string
    {
        $normalized = strtoupper(trim((string) $value));

        return in_array($normalized, self::all(), true) ? $normalized : $default;
    }

    public static function isInternational(string $standard): bool
    {
        return in_array(strtoupper(trim($standard)), [self::EMEA, self::ANZ], true);
    }
}
