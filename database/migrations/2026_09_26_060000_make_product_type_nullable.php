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
        // Legacy single-type column (tags live in product_type pivot now); products may have no tags.
        Schema::table('products', function (Blueprint $table) {
            $table->string('product_type')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->string('product_type')->nullable(false)->change();
        });
    }
};
