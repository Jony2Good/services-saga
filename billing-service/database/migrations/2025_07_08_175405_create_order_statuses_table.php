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
        Schema::create('order_statuses', function (Blueprint $table) {
            $table->id();
            $table->integer('order_id')->unsigned();
            $table->foreignId('billing_account_id')->constrained('billing_accounts')->onDelete('cascade');
            $table->integer('status')->default(0);
            $table->string('request_key')->nullable();
            $table->string('saga_id');
            $table->string('total_price')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('order_statuses');
    }
};
