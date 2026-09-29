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
        Schema::create('email_otps', function (Blueprint $table) {
            $table->id();
            $table->string('email')->index();
            $table->string('code_hash', 64);
            $table->timestamp('expires_at');
            $table->integer('attempts')->default(0);
            $table->timestamp('used_at')->nullable();
            $table->timestamps();
        });

        Schema::table('users', function (Blueprint $table) {
            $table->string('password')->nullable()->change();
            $table->string('display_name')->nullable()->unique();
            $table->string('full_name')->nullable();
            $table->string('address', 500)->nullable();
            $table->decimal('address_lat', 10, 7)->nullable();
            $table->decimal('address_lng', 10, 7)->nullable();
        });

        Schema::table('orders', function (Blueprint $table) {
            $table->string('contact_name')->nullable();
            $table->string('address')->nullable();
            $table->decimal('address_lat', 10, 7)->nullable();
            $table->decimal('address_lng', 10, 7)->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropColumn(['contact_name', 'address', 'address_lat', 'address_lng']);
        });
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('display_name');
        });
        Schema::dropIfExists('email_otps');
    }
};
