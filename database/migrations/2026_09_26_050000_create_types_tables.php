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
        Schema::create('types', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->timestamps();
        });

        Schema::create('product_type', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('product_id');
            $table->unsignedBigInteger('type_id');
            $table->timestamps();

            $table->unique(['product_id', 'type_id']);
            $table->foreign('product_id')->references('id')->on('products')->onDelete('cascade');
            $table->foreign('type_id')->references('id')->on('types')->onDelete('cascade');
        });

        // Backfill tags from the legacy single-type column (kept for Blade compat).
        $names = DB::table('products')->distinct()->pluck('product_type');
        foreach ($names as $name) {
            if (! $name) {
                continue;
            }
            $typeId = DB::table('types')->insertGetId([
                'name' => $name,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
            $ids = DB::table('products')->where('product_type', $name)->pluck('id');
            foreach ($ids as $pid) {
                DB::table('product_type')->insert([
                    'product_id' => $pid,
                    'type_id' => $typeId,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('product_type');
        Schema::dropIfExists('types');
    }
};
