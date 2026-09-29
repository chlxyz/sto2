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
        Schema::create('addresses', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->string('label')->default('Home');
            $table->string('contact_name')->nullable();
            $table->string('address', 500);
            $table->decimal('address_lat', 10, 7)->nullable();
            $table->decimal('address_lng', 10, 7)->nullable();
            $table->timestamps();

            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
        });

        // Backfill one entry per user that already has a profile address.
        foreach (DB::table('users')->whereNotNull('address')->get(['id', 'full_name', 'address', 'address_lat', 'address_lng']) as $u) {
            if (! trim((string) $u->address)) {
                continue;
            }
            DB::table('addresses')->insert([
                'user_id' => $u->id,
                'label' => 'Home',
                'contact_name' => $u->full_name,
                'address' => $u->address,
                'address_lat' => $u->address_lat,
                'address_lng' => $u->address_lng,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('addresses');
    }
};
