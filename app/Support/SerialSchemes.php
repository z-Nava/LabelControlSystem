<?php

namespace App\Support;

final class SerialSchemes
{
    public const UL_STANDARD = 'ul_standard';
    public const EMEA_RATING = 'emea_rating';
    public const ANZ_STANDARD = 'anz_standard';

    /**
     * @return array<int, string>
     */
    public static function all(): array
    {
        return [
            self::UL_STANDARD,
            self::EMEA_RATING,
            self::ANZ_STANDARD,
        ];
    }
}
