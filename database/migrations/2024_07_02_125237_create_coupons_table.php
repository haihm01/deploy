<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('coupons', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('description');
            $table->string('code');
            $table->integer('discount');
            $table->enum('discount_type', ['fixed', 'percentage']);
            $table->integer('usage_limit');
            $table->integer('min_order_value')->nullable();
            $table->integer('max_order_value')->nullable();
            $table->date('start_date')->nullable();
            $table->date('end_date')->nullable();
            $table->boolean('status')->default(true);
            $table->float('winning_probability')->default(0.1); 
            $table->enum('type', ['coupon', 'event']);
            $table->timestamps();

        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('coupons');
    }
};
