<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ledger_batches', function (Blueprint $table) {
            $table->id();
            $table->uuid('ref')->unique()->comment('External reference UUID');
            $table->string('idempotency', 128)->unique()->nullable();
            $table->string('name');
            $table->string('channel', 32)->default('api');
            $table->string('status', 32)->default('posted');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ledger_batches');
    }
};
