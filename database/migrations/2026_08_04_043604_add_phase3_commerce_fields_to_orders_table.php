<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->string('payment_gateway', 32)->default('manual')->after('payment_method');
            $table->string('payment_transaction_id', 100)->nullable()->after('payment_gateway');
            $table->json('payment_payload')->nullable()->after('payment_transaction_id');
            $table->timestamp('payment_expires_at')->nullable()->after('paid_at');
            $table->string('shipping_service', 50)->nullable()->after('shipping_cost');
            $table->string('shipping_etd', 50)->nullable()->after('shipping_service');
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropColumn([
                'payment_gateway',
                'payment_transaction_id',
                'payment_payload',
                'payment_expires_at',
                'shipping_service',
                'shipping_etd',
            ]);
        });
    }
};
