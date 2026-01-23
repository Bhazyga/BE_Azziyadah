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
        Schema::create('transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('item_id')->constrained('items')->onDelete('cascade');
            $table->string('transaction_id')->unique();
            $table->string('midtrans_order_id')->unique();
            $table->integer('jumlah')->default(1);
            $table->integer('total_harga');
            $table->enum('status', ['pending', 'success', 'failed', 'expire', 'cancelled','settlement'])->default('pending');
            $table->string('payment_type')->nullable();
            $table->json('midtrans_response')->nullable();
            $table->timestamp('transaction_time')->useCurrent();
            $table->timestamp('payment_time')->nullable();
            $table->timestamps();
        });

    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('transactions');
    }
};
