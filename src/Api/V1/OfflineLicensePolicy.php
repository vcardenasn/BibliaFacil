<?php

namespace Biblia\Api\V1;

final class OfflineLicensePolicy
{
    private const ALLOWED_LICENSES = ['public_domain', 'cc-by-4.0', 'cc-by-sa-4.0'];

    public static function allows(string $license): bool
    {
        return in_array($license, self::ALLOWED_LICENSES, true);
    }
}
