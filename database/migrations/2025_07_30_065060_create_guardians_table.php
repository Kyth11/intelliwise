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
        Schema::create('guardians', function (Blueprint $table) {
            $table->id();
            $table->string('g_firstname');
            $table->string('g_lastname');
            $table->string('g_middlename')->nullable();
            $table->string('g_address');
            $table->string('g_contact');
            $table->string('g_email')->nullable();
            $table->unsignedBigInteger('tuition_id')->nullable();
            $table->unsignedBigInteger(column: 'payment_id')->nullable();

            $table->foreign('tuition_id')->references('id')->on('tuitions')->onDelete('cascade')->onUpdate('cascade');
            $table->foreign('payment_id')->references('id')->on('payments')->onDelete('cascade')->onUpdate('cascade');

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('guardians');
    }
};
