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
 * @property CarbonImmutable|null $closed_at
 * @property CarbonImmutable|null $created_at
 * @property CarbonImmutable|null $updated_at
 */
#[Fillable(['title', 'baby_name', 'baby_gender', 'intro', 'iban', 'account_holder', 'wero_phone'])]
class GiftList extends Model
{
    /** @use HasFactory<GiftListFactory> */
    use HasFactory;

    protected function casts(): array
    {
        return [
            'baby_gender' => BabyGender::class,
            'closed_at' => 'datetime',
        ];
    }

    /**
     * The IBAN in groups of four, which reads and copies over more easily
     * than one long string. Bank apps accept the spaces.
     */
    public function formattedIban(): ?string
    {
        if ($this->iban === null) {
            return null;
        }

        $iban = mb_strtoupper(str_replace(' ', '', $this->iban));

        return trim(chunk_split($iban, 4, ' '));
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
