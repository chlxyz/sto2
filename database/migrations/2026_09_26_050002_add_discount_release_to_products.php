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
        Schema::table('products', function (Blueprint $table) {
            $table->integer('discount_percent')->default(0);
            $table->timestamp('released_at')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn(['discount_percent', 'released_at']);
        });
    }
};
