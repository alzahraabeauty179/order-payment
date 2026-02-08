<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('order_products', function (Blueprint $table) {
            $table->string('product_name_snapshot')->after('product_id');
            $table->decimal('unit_price_snapshot', 10, 2)->after('product_name_snapshot')->default(0);
            $table->decimal('total_price_snapshot', 10, 2)->after('quantity')->default(0);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('order_products', function (Blueprint $table) {
            $table->dropColumn([
                'product_name_snapshot',
                'unit_price_snapshot',
                'total_price_snapshot',
            ]);
        });
    }
};
