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
        Schema::create('bank_transactions', function (Blueprint $table) {
            $table->id();
            $table->string('fingerprint')->unique();
            $table->string('counterparty_name')->nullable();
            $table->string('counterparty_account')->nullable();
            $table->text('description')->nullable();
            $table->unsignedInteger('amount');
            $table->foreignId('contribution_id')->nullable()->constrained()->nullOnDelete();
            $table->date('booked_at');
            $table->timestamp('ignored_at')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('bank_transactions');
    }
};
