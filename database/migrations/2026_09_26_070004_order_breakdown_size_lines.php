<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    // ponytail: pooled pgsql aborts multi-statement DDL transactions; run autocommit.
    public $withinTransaction = false;

    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->decimal('subtotal', 8, 2)->default(0);
            $table->decimal('discount_total', 8, 2)->default(0);
            $table->json('voucher_codes')->nullable();
            $table->decimal('voucher_amount', 8, 2)->default(0);
            $table->decimal('tax_amount', 8, 2)->default(0);
            $table->decimal('shipping_amount', 8, 2)->default(0);
        });

        Schema::table('cart_item', function (Blueprint $table) {
            $table->unsignedBigInteger('size_id')->nullable();
            $table->foreign('size_id')->references('id')->on('sizes')->onDelete('set null');
        });

        Schema::table('order_items', function (Blueprint $table) {
            $table->unsignedBigInteger('size_id')->nullable();
            $table->string('size_label')->nullable();
            $table->foreign('size_id')->references('id')->on('sizes')->onDelete('set null');
        });
    }

    public function down(): void
    {
        Schema::table('order_items', function (Blueprint $table) {
            $table->dropConstrainedForeignId('size_id');
            $table->dropColumn('size_label');
        });
        Schema::table('cart_item', function (Blueprint $table) {
            $table->dropConstrainedForeignId('size_id');
        });
        Schema::table('orders', function (Blueprint $table) {
            $table->dropColumn(['subtotal', 'discount_total', 'voucher_codes', 'voucher_amount', 'tax_amount', 'shipping_amount']);
        });
    }
};
