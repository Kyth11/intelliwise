<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('guardians', function (Blueprint $table) {
            $table->id();

            // Household contact + address (legacy-friendly)
            $table->string('g_address')->nullable();
            $table->string('g_contact')->nullable();
            $table->string('g_email')->nullable();

            // Mother
            $table->string('m_firstname')->nullable();
            $table->string('m_middlename')->nullable();
            $table->string('m_lastname')->nullable();
            $table->string('m_contact')->nullable();
            $table->string('m_email')->nullable();

            // Father
            $table->string('f_firstname')->nullable();
            $table->string('f_middlename')->nullable();
            $table->string('f_lastname')->nullable();
            $table->string('f_contact')->nullable();
            $table->string('f_email')->nullable();

            // Optional relations you had
            $table->unsignedBigInteger('tuition_id')->nullable();
            $table->unsignedBigInteger('payment_id')->nullable();

            $table->foreign('tuition_id')->references('id')->on('tuitions')->onDelete('cascade')->onUpdate('cascade');
            $table->foreign('payment_id')->references('id')->on('payments')->onDelete('cascade')->onUpdate('cascade');

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('guardians');
    }
};
