<?php

namespace App\Models;

use App\Enums\ContributionEventAction;
use App\Enums\ContributionStatus;
use App\Enums\ContributionType;
use App\Models\Builders\ContributionBuilder;
use Carbon\CarbonImmutable;
use Database\Factories\ContributionFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\UseEloquentBuilder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

/**
 * @property int $id
 * @property string $reference
 * @property ContributionType $type
 * @property ContributionStatus $status
 * @property string $name
 * @property string|null $together_with
 * @property int|null $amount
 * @property string|null $message
 * @property int|null $gift_id
 * @property int $user_id
 * @property CarbonImmutable|null $confirmed_at
 * @property CarbonImmutable|null $cancelled_at
 * @property CarbonImmutable|null $created_at
 * @property CarbonImmutable|null $updated_at
 */
#[Fillable(['name', 'together_with', 'amount', 'message'])]
#[UseEloquentBuilder(ContributionBuilder::class)]
class Contribution extends Model
{
    /** @use HasFactory<ContributionFactory> */
    use HasFactory;

    protected $attributes = [
        'status' => 'pending',
    ];

    protected function casts(): array
    {
        return [
            'type' => ContributionType::class,
            'status' => ContributionStatus::class,
            'amount' => 'integer',
            'gift_id' => 'integer',
            'user_id' => 'integer',
            'confirmed_at' => 'datetime',
            'cancelled_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<Gift, $this>
     */
    public function gift(): BelongsTo
    {
        return $this->belongsTo(Gift::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * @return HasOne<BankTransaction, $this>
     */
    public function bankTransaction(): HasOne
    {
        return $this->hasOne(BankTransaction::class);
    }

    /**
     * @return HasMany<ContributionEvent, $this>
     */
    public function events(): HasMany
    {
        return $this->hasMany(ContributionEvent::class);
    }

    public function logEvent(ContributionEventAction $action, ?User $actor = null): void
    {
        $event = new ContributionEvent(['action' => $action]);

        if ($actor !== null) {
            $event->user()->associate($actor);
        }

        $this->events()->save($event);
    }

    /**
     * The full donor display name, including who they are giving together with.
     */
    public function displayName(): string
    {
        if ($this->together_with === null) {
            return $this->name;
        }

        return "{$this->name} & {$this->together_with}";
    }

    public function isPending(): bool
    {
        return $this->status === ContributionStatus::Pending;
    }

    public function isConfirmed(): bool
    {
        return $this->status === ContributionStatus::Confirmed;
    }

    public function isCancelled(): bool
    {
        return $this->status === ContributionStatus::Cancelled;
    }

    public function isPurchase(): bool
    {
        return $this->type === ContributionType::Purchase;
    }

    /**
     * A free contribution is not tied to any gift.
     */
    public function isFree(): bool
    {
        return $this->gift_id === null;
    }

    public function giftTitle(): string
    {
        return $this->gift->title ?? 'Vrije bijdrage';
    }

    public function isStale(): bool
    {
        return $this->isPending() && $this->created_at?->lt(now()->subWeek()) === true;
    }

    /**
     * The reference as it should be looked up in a bank statement: uppercase, alphanumerics only.
     */
    public function normalizedReference(): string
    {
        return preg_replace('/[^A-Z0-9]/', '', mb_strtoupper($this->reference)) ?? '';
    }

    public static function generateReference(): string
    {
        $alphabet = '23456789ABCDEFGHJKMNPQRSTUVWXYZ';

        do {
            $code = '';

            for ($i = 0; $i < 6; $i++) {
                $code .= $alphabet[random_int(0, mb_strlen($alphabet) - 1)];
            }

            $reference = "BL-{$code}";
        } while (self::query()->where('reference', $reference)->exists());

        return $reference;
    }
}
