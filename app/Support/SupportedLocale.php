<?php

namespace App\Support;

final class SupportedLocale
{
    public const DEFAULT = 'en';

    public const VALUES = ['en', 'tl'];

    public static function isSupported(mixed $locale): bool
    {
        return is_string($locale) && in_array($locale, self::VALUES, true);
    }

    public static function normalize(mixed $locale): string
    {
        return self::isSupported($locale) ? $locale : self::DEFAULT;
    }
}
