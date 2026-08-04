<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('reviews', function (Blueprint $table) {
            $table->foreignId('order_id')
                ->nullable()
                ->after('product_id')
                ->constrained()
                ->nullOnDelete();

            $table->unique(['product_id', 'order_id']);
        });
    }

    public function down(): void
    {
        Schema::table('reviews', function (Blueprint $table) {
            $table->dropUnique(['product_id', 'order_id']);
            $table->dropConstrainedForeignId('order_id');
        });
    }
};
