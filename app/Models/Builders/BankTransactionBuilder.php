<?php

namespace App\Models\Builders;

use App\Models\BankTransaction;
use Illuminate\Database\Eloquent\Builder;

/**
 * @extends Builder<BankTransaction>
 */
class BankTransactionBuilder extends Builder
{
    public function unmatched(): self
    {
        return $this->whereNull('contribution_id')->whereNull('ignored_at');
    }
}
