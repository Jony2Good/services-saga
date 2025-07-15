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
        Schema::create('stock_orders', function (Blueprint $table) {
            $table->id();           
            $table->unsignedInteger('order_id');
            $table->string('saga_id')->nullable();
            $table->integer('status')->default(0);           
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('stock_orders');
    }
};
