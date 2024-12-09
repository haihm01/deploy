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
        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->integer('grand_total');
            $table->integer('final_total')->nullable();
            $table->string('payment_method');
            $table->string('status_order')->default(\App\Models\Order::STATUS_ORDER_PENDING);
            $table->string('code');
            $table->text('notes')->nullable();
            $table->string('name');
            $table->string('tel');
            $table->string('address');
            $table->timestamp('paid_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->integer('profit')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('orders');
    }
};