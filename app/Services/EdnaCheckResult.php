<?php

namespace App\Services;

class EdnaCheckResult
{
    public function __construct(
        public bool $signed,
        public ?string $rawStatus,
    ) {}
}
