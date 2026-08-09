<?php

namespace Database\Seeders;

use App\Models\GiftList;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class GiftListSeeder extends Seeder
{
    /**
     * Seed the single gift list the application revolves around.
     */
    public function run(): void
    {
        if (GiftList::query()->exists()) {
            return;
        }

        GiftList::factory()->create([
            'slug' => Str::random(24),
            'title' => 'Onze geboortelijst',
            'intro' => 'Welkom op onze geboortelijst! Hier vind je alles wat we graag zouden krijgen voor onze kleine spruit.',
            'iban' => null,
            'account_holder' => null,
            'wero_phone' => null,
            'expected_at' => null,
        ]);
    }
}
