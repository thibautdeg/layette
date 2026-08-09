<?php

namespace App\DTO;

class FreeContributionData
{
    public function __construct(
        public int $amount,
        public ?string $message,
        public ?string $togetherWith,
    ) {}
}
