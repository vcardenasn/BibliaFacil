<?php

namespace Biblia\Core;

final class FeatureFlags
{
    public static function enabled(string $flag): bool
    {
        return env($flag, '0') === '1';
    }

    public static function requireEnabled(string $flag): void
    {
        if (!self::enabled($flag)) {
            throw new FeatureDisabledException($flag);
        }
    }
}
