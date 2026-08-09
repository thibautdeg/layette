<?php

namespace App\DTO;

class BankStatementImportResultData
{
    public function __construct(
        public int $created,
        public int $duplicates,
        public int $matched,
        public int $unmatched,
    ) {}
}
