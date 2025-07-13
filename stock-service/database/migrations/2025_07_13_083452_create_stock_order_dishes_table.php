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
        Schema::create('stock_order_dishes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('stock_order_id')->constrained('stock_orders')->onDelete('cascade');
            $table->unsignedInteger('dish_id');
            $table->unsignedInteger('amount');           
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('stock_order_dishes');
    }
};
