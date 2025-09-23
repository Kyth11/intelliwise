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
        Schema::create('paymentrecord', function (Blueprint $table) {
            $table->id();
            $table->decimal('amount_paid', 10, 2);
            $table->date('payment_date');
            $table->enum('payment_method', ['Cash', 'G-cash']);
            $table->string('transaction_id')->nullable(); // For tracking payment transactions
            $table->unsignedBigInteger('guardian_id');
            $table->unsignedBigInteger('payment_id');
            $table->foreign('guardian_id')->references('id')->on('guardian')->onDelete('cascade')->onUpdate('cascade');
            $table->foreign('payment_id')->references('id')->on('payments')->onDelete('cascade')->onUpdate('cascade');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('paymentrecord');
    }
};
