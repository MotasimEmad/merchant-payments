<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('wallets', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->morphs('holder');
            $table->char('currency', 3)->default('USD');
            $table->string('label')->nullable();
            $table->string('status', 32)->default('active');
            $table->timestamps();

            $table->unique(['holder_type', 'holder_id', 'currency'], 'wallets_holder_currency_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('wallets');
    }
};
