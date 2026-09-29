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
        Schema::create('sizes', function (Blueprint $table) {
            $table->id();
            $table->string('label')->unique();
            $table->timestamps();
        });

        Schema::create('product_size', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('product_id');
            $table->unsignedBigInteger('size_id');
            $table->integer('stock')->default(0);
            $table->timestamps();

            $table->unique(['product_id', 'size_id']);
            $table->foreign('product_id')->references('id')->on('products')->onDelete('cascade');
            $table->foreign('size_id')->references('id')->on('sizes')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('product_size');
        Schema::dropIfExists('sizes');
    }
};
