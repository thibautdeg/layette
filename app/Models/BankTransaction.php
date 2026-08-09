<?php

namespace App\Models;

use App\Models\Builders\BankTransactionBuilder;
use Carbon\CarbonImmutable;
use Database\Factories\BankTransactionFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\UseEloquentBuilder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property string $fingerprint
 * @property string|null $counterparty_name
 * @property string|null $counterparty_account
 * @property string|null $description
 * @property int $amount
 * @property int|null $contribution_id
 * @property CarbonImmutable $booked_at
 * @property CarbonImmutable|null $ignored_at
 * @property CarbonImmutable|null $created_at
 * @property CarbonImmutable|null $updated_at
 */
#[Fillable(['fingerprint', 'counterparty_name', 'counterparty_account', 'description', 'amount', 'booked_at'])]
#[UseEloquentBuilder(BankTransactionBuilder::class)]
class BankTransaction extends Model
{
    /** @use HasFactory<BankTransactionFactory> */
    use HasFactory;

    protected function casts(): array
    {
        return [
            'amount' => 'integer',
            'contribution_id' => 'integer',
            'booked_at' => 'date',
            'ignored_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<Contribution, $this>
     */
    public function contribution(): BelongsTo
    {
        return $this->belongsTo(Contribution::class);
    }

    public function isMatched(): bool
    {
        return $this->contribution_id !== null;
    }

    public function isIgnored(): bool
    {
        return $this->ignored_at !== null;
    }

    /**
     * The statement description as used for reference matching: uppercase, alphanumerics only.
     */
    public function normalizedDescription(): string
    {
        return preg_replace('/[^A-Z0-9]/', '', mb_strtoupper($this->description ?? '')) ?? '';
    }
}
