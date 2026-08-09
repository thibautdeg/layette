<?php

namespace App\Models;

use App\Enums\BabyGender;
use Carbon\CarbonImmutable;
use Database\Factories\GiftListFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property int $id
 * @property string $slug
 * @property string $title
 * @property string|null $baby_name
 * @property BabyGender|null $baby_gender
 * @property string|null $intro
 * @property string|null $photo_path
 * @property string|null $iban
 * @property string|null $account_holder
 * @property string|null $wero_phone
 * @property CarbonImmutable|null $expected_at
 * @property CarbonImmutable|null $closed_at
 * @property CarbonImmutable|null $created_at
 * @property CarbonImmutable|null $updated_at
 */
#[Fillable(['title', 'baby_name', 'baby_gender', 'intro', 'iban', 'account_holder', 'wero_phone', 'expected_at'])]
class GiftList extends Model
{
    /** @use HasFactory<GiftListFactory> */
    use HasFactory;

    protected function casts(): array
    {
        return [
            'baby_gender' => BabyGender::class,
            'expected_at' => 'date',
            'closed_at' => 'datetime',
        ];
    }

    /**
     * Days until the due date, or null when no due date is set or it has passed.
     */
    public function daysUntilDue(): ?int
    {
        if ($this->expected_at === null) {
            return null;
        }

        $days = (int) today()->diffInDays($this->expected_at, false);

        return $days >= 0 ? $days : null;
    }

    /**
     * The current pregnancy week, assuming a 40-week term counted back from the due date.
     */
    public function pregnancyWeek(): ?int
    {
        $days = $this->daysUntilDue();

        if ($days === null) {
            return null;
        }

        return max(1, min(40, 40 - intdiv($days, 7)));
    }

    /**
     * @return HasMany<Gift, $this>
     */
    public function gifts(): HasMany
    {
        return $this->hasMany(Gift::class);
    }

    public static function current(): self
    {
        return self::query()->firstOrFail();
    }

    public function isClosed(): bool
    {
        return $this->closed_at !== null;
    }
}
