<?php

namespace App\Models\Builders;

use App\Models\Gift;
use Illuminate\Database\Eloquent\Builder;

/**
 * @extends Builder<Gift>
 */
class GiftBuilder extends Builder
{
    public function visible(): self
    {
        return $this->whereNull('hidden_at');
    }

    public function ordered(): self
    {
        return $this->orderBy('position')->orderBy('id');
    }
}
