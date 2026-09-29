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
        Schema::table('vouchers', function (Blueprint $table) {
            $table->decimal('max_discount', 8, 2)->nullable();
            $table->boolean('exclude_sale_items')->default(false);
        });
    }

    public function down(): void
    {
        Schema::table('vouchers', function (Blueprint $table) {
            $table->dropColumn(['max_discount', 'exclude_sale_items']);
        });
    }
};
