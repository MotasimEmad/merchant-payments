<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ledger_lines', function (Blueprint $table) {
            $table->id();
            $table->foreignId('batch_id')->constrained('ledger_batches')->cascadeOnDelete();
            $table->foreignId('wallet_id')->constrained()->cascadeOnDelete();
            $table->bigInteger('cents')->comment('Signed amount in minor units; sum per batch = 0');
            $table->string('type', 64);
            $table->nullableMorphs('link');
            $table->json('extra')->nullable();
            $table->timestamps();

            $table->index(['wallet_id', 'id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ledger_lines');
    }
};
