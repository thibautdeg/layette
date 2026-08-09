<?php

namespace App\Models;

use App\Enums\ContributionType;
use App\Enums\GiftStatus;
use App\Models\Builders\GiftBuilder;
use Carbon\CarbonImmutable;
use Database\Factories\GiftFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\UseEloquentBuilder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property int $id
 * @property string $title
 * @property string|null $description
 * @property int $price
 * @property string|null $image_path
 * @property string|null $shop_url
 * @property bool $allows_partial_contributions
 * @property bool $allows_purchase
 * @property int $position
 * @property int $gift_list_id
 * @property CarbonImmutable|null $hidden_at
 * @property CarbonImmutable|null $created_at
 * @property CarbonImmutable|null $updated_at
 */
#[Fillable(['title', 'description', 'price', 'shop_url', 'allows_partial_contributions', 'allows_purchase'])]
#[UseEloquentBuilder(GiftBuilder::class)]
class Gift extends Model
{
    /** @use HasFactory<GiftFactory> */
    use HasFactory;

    protected function casts(): array
    {
        return [
            'price' => 'integer',
            'allows_partial_contributions' => 'boolean',
            'allows_purchase' => 'boolean',
            'position' => 'integer',
            'gift_list_id' => 'integer',
            'hidden_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<GiftList, $this>
     */
    public function giftList(): BelongsTo
    {
        return $this->belongsTo(GiftList::class);
    }

    /**
     * @return HasMany<Contribution, $this>
     */
    public function contributions(): HasMany
    {
        return $this->hasMany(Contribution::class);
    }

    /**
     * All contributions that still count: pending and confirmed, never cancelled.
     *
     * @return Collection<int, Contribution>
     */
    public function activeContributions(): Collection
    {
        return $this->contributions->filter(fn (Contribution $contribution): bool => ! $contribution->isCancelled())->values();
    }

    public function pledgedAmount(): int
    {
        return (int) $this->activeContributions()
            ->where('type', ContributionType::Contribution)
            ->sum('amount');
    }

    public function confirmedAmount(): int
    {
        return (int) $this->contributions
            ->filter(fn (Contribution $contribution): bool => $contribution->isConfirmed())
            ->where('type', ContributionType::Contribution)
            ->sum('amount');
    }

    public function remainingAmount(): int
    {
        return max(0, $this->price - $this->pledgedAmount());
    }

    public function isReserved(): bool
    {
        return $this->activeContributions()->contains(
            fn (Contribution $contribution): bool => $contribution->type === ContributionType::Purchase
        );
    }

    public function isFull(): bool
    {
        return $this->price > 0 && $this->pledgedAmount() >= $this->price;
    }

    public function isHidden(): bool
    {
        return $this->hidden_at !== null;
    }

    public function hasActiveContributions(): bool
    {
        return $this->activeContributions()
            ->contains(fn (Contribution $contribution): bool => $contribution->type === ContributionType::Contribution);
    }

    public function isAvailable(): bool
    {
        return ! $this->isHidden() && ! $this->isReserved() && ! $this->isFull();
    }

    public function status(): GiftStatus
    {
        if ($this->isHidden()) {
            return GiftStatus::Hidden;
        }

        if ($this->isReserved()) {
            return GiftStatus::Reserved;
        }

        if ($this->isFull()) {
            return GiftStatus::Full;
        }

        return GiftStatus::Open;
    }
}
