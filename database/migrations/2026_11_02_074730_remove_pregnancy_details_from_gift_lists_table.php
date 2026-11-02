<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        DB::table('gift_lists')->where('baby_gender', 'surprise')->update(['baby_gender' => null]);

        Schema::table('gift_lists', function (Blueprint $table) {
            $table->dropColumn('expected_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('gift_lists', function (Blueprint $table) {
            $table->date('expected_at')->nullable();
        });
    }
};
