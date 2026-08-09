<?php

namespace App\Models;

use Carbon\CarbonImmutable;
use Database\Factories\NameGuessFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

/**
 * @property int $id
 * @property string $name
 * @property int $user_id
 * @property CarbonImmutable|null $created_at
 * @property CarbonImmutable|null $updated_at
 */
#[Fillable(['name'])]
class NameGuess extends Model
{
    /** @use HasFactory<NameGuessFactory> */
    use HasFactory;

    protected function casts(): array
    {
        return [
            'user_id' => 'integer',
        ];
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Normalize a guessed name so "nora", "NORA" and "Nora" count as the same guess.
     */
    public static function normalize(string $name): string
    {
        return Str::title(mb_strtolower(trim($name)));
    }
}
