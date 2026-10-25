<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('warehouse_products', function (Blueprint $table) {
            $table->decimal('stock', 12, 3)->change();
        });

        Schema::table('transactions', function (Blueprint $table) {
            $table->string('idempotency_key', 64)->nullable()->unique()->after('merchant_id');
        });

        Schema::table('stock_outs', function (Blueprint $table) {
            $table->foreignId('warehouse_id')->nullable()->after('merchant_id')
                ->constrained()->nullOnDelete();
            $table->foreignId('transaction_id')->nullable()->after('warehouse_id')
                ->constrained()->nullOnDelete();
            $table->decimal('quantity', 12, 3)->change();
            $table->unique(
                ['transaction_id', 'product_id', 'warehouse_id'],
                'stock_outs_transaction_product_warehouse_unique'
            );
        });
    }

    public function down(): void
    {
        Schema::table('stock_outs', function (Blueprint $table) {
            $table->dropUnique('stock_outs_transaction_product_warehouse_unique');
            $table->dropConstrainedForeignId('transaction_id');
            $table->dropConstrainedForeignId('warehouse_id');
            $table->unsignedInteger('quantity')->change();
        });

        Schema::table('transactions', function (Blueprint $table) {
            $table->dropUnique(['idempotency_key']);
            $table->dropColumn('idempotency_key');
        });

        Schema::table('warehouse_products', function (Blueprint $table) {
            $table->unsignedInteger('stock')->change();
        });
    }
};
