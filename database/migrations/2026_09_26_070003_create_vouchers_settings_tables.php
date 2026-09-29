<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    // ponytail: pooled pgsql aborts multi-statement DDL transactions; run autocommit.
    public $withinTransaction = false;

    public function up(): void
    {
        Schema::create('vouchers', function (Blueprint $table) {
            $table->id();
            $table->string('code')->unique();
            $table->string('type')->default('percent');
            $table->decimal('value', 8, 2);
            $table->decimal('min_subtotal', 8, 2)->default(0);
            $table->timestamp('expires_at')->nullable();
            $table->integer('max_uses')->nullable();
            $table->integer('used_count')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('settings', function (Blueprint $table) {
            $table->id();
            $table->string('key')->unique();
            $table->string('value')->default('');
            $table->timestamps();
        });

        DB::table('settings')->insert([
            ['key' => 'tax_percent', 'value' => '10', 'created_at' => now(), 'updated_at' => now()],
            ['key' => 'shipping_flat', 'value' => '5', 'created_at' => now(), 'updated_at' => now()],
            ['key' => 'free_shipping_min', 'value' => '50', 'created_at' => now(), 'updated_at' => now()],
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('settings');
        Schema::dropIfExists('vouchers');
    }
};
