<?php

use Biblia\Api\V1\OfflineLicensePolicy;

return function (TestCase $t): void {
    $t->run('offline license policy allows public domain and Creative Commons', function () use ($t) {
        $t->assertSame([
            true,
            true,
            true,
            false,
            false,
        ], [
            OfflineLicensePolicy::allows('public_domain'),
            OfflineLicensePolicy::allows('cc-by-4.0'),
            OfflineLicensePolicy::allows('cc-by-sa-4.0'),
            OfflineLicensePolicy::allows('free-distribution'),
            OfflineLicensePolicy::allows('copyrighted'),
        ]);
    });
};
