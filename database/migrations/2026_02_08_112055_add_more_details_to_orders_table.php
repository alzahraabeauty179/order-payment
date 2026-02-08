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
        Schema::table('orders', function (Blueprint $table) {
            $table->decimal('subtotal_amount', 10, 2)->after('total_items')->default(0);
            $table->decimal('discount_amount', 10, 2)->after('subtotal_amount')->default(0);
            $table->decimal('tax_amount', 10, 2)->after('discount_amount')->default(0);
            $table->decimal('total_amount', 10, 2)->after('tax_amount')->default(0);
            $table->string('currency', 3)->after('total_amount')->default('EGP');
            $table->timestamp('expires_at')->after('currency');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropColumn([
                'subtotal_amount',
                'discount_amount',
                'tax_amount',
                'total_amount',
                'currency',
            ]);
        });
    }
};
