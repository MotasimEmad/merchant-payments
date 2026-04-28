<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('ledger_batches') || ! Schema::hasColumn('ledger_batches', 'public_id')) {
            return;
        }

        if (Schema::hasColumn('payment_intents', 'ledger_batch_id')) {
            Schema::table('payment_intents', function (Blueprint $table) {
                $table->dropForeign(['ledger_batch_id']);
            });
        }

        if (Schema::hasColumn('ledger_lines', 'ledger_batch_id')) {
            Schema::table('ledger_lines', function (Blueprint $table) {
                $table->dropForeign(['ledger_batch_id']);
            });
        }
        if (Schema::hasColumn('ledger_lines', 'wallet_id')) {
            Schema::table('ledger_lines', function (Blueprint $table) {
                $table->dropForeign(['wallet_id']);
            });
        }

        Schema::table('ledger_batches', function (Blueprint $table) {
            $table->renameColumn('public_id', 'ref');
            $table->renameColumn('idempotency_key', 'idempotency');
            $table->renameColumn('title', 'name');
            $table->renameColumn('source', 'channel');
        });

        Schema::table('ledger_lines', function (Blueprint $table) {
            $table->renameColumn('ledger_batch_id', 'batch_id');
            $table->renameColumn('amount_minor', 'cents');
            $table->renameColumn('entry_code', 'type');
            $table->renameColumn('reference_type', 'link_type');
            $table->renameColumn('reference_id', 'link_id');
            $table->renameColumn('metadata', 'extra');
        });

        Schema::table('payment_intents', function (Blueprint $table) {
            $table->renameColumn('idempotency_key', 'idempotency');
            $table->renameColumn('ledger_batch_id', 'batch_id');
        });

        Schema::table('ledger_lines', function (Blueprint $table) {
            $table->foreign('batch_id')->references('id')->on('ledger_batches')->cascadeOnDelete();
            $table->foreign('wallet_id')->references('id')->on('wallets')->cascadeOnDelete();
        });
        Schema::table('payment_intents', function (Blueprint $table) {
            $table->foreign('batch_id')->references('id')->on('ledger_batches')->nullOnDelete();
        });
    }

    public function down(): void
    {
    }
};
