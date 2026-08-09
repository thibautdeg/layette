<?php

namespace App\DTO;

use App\Enums\ContributionType;

class ContributionData
{
    public function __construct(
        public ContributionType $type,
        public ?int $amount,
        public ?string $message,
        public ?string $togetherWith,
    ) {}
}
