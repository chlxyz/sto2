<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    // ponytail: pooled pgsql aborts multi-statement DDL transactions; run autocommit.
    public $withinTransaction = false;

    public function up(): void
    {
        DB::statement('ALTER TABLE products ADD CONSTRAINT products_quantity_nonnegative CHECK (product_quantity >= 0)');
        DB::statement('ALTER TABLE product_size ADD CONSTRAINT product_size_stock_nonnegative CHECK (stock >= 0)');
    }

    public function down(): void
    {
        DB::statement('ALTER TABLE product_size DROP CONSTRAINT product_size_stock_nonnegative');
        DB::statement('ALTER TABLE products DROP CONSTRAINT products_quantity_nonnegative');
    }
};
