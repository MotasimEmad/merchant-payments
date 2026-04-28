<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('payment_intents', function (Blueprint $table) {
            $table->foreignId('merchant_service_id')
                ->nullable()
                ->after('merchant_id')
                ->constrained('merchant_services')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('payment_intents', function (Blueprint $table) {
            $table->dropConstrainedForeignId('merchant_service_id');
        });
    }
};
