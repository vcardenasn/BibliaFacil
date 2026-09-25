<?php

namespace Biblia\Core;

use RuntimeException;

final class FeatureDisabledException extends RuntimeException
{
    public function __construct(public readonly string $flag)
    {
        parent::__construct("Feature disabled: {$flag}");
    }
}
