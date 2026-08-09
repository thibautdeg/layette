<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('gifts', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->text('description')->nullable();
            $table->unsignedInteger('price');
            $table->string('image_path')->nullable();
            $table->string('shop_url')->nullable();
            $table->boolean('allows_partial_contributions')->default(true);
            $table->boolean('allows_purchase')->default(true);
            $table->unsignedInteger('position')->default(0)->index();
            $table->foreignId('gift_list_id')->constrained()->cascadeOnDelete();
            $table->timestamp('hidden_at')->nullable()->index();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('gifts');
    }
};
