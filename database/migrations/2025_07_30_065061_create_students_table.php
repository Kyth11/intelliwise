<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('students', function (Blueprint $table) {
            // LRN as PRIMARY KEY (string/char PK; no auto-increment id)
            $table->char('lrn', 12)->primary();

            $table->string('s_firstname');
            $table->string('s_middlename')->nullable();
            $table->string('s_lastname');
            $table->date('s_birthdate');
            $table->string('s_address');
            $table->string('s_citizenship')->nullable();
            $table->string('s_religion')->nullable();
            $table->string('s_contact')->nullable();
            $table->string('s_email')->nullable();

            // Guardian household
            $table->unsignedBigInteger('guardian_id')->nullable();
            $table->foreign('guardian_id')->references('id')->on('guardians')->onDelete('set null');

            // Grade Level (free text + FK)
            $table->string('s_gradelvl')->nullable();
            $table->unsignedBigInteger('gradelvl_id')->nullable();
            $table->foreign('gradelvl_id')->references('id')->on('gradelvls')->onDelete('set null');

            // Tuition (denormalized sum + FK)
            $table->string('s_tuition_sum')->nullable();
            $table->unsignedBigInteger('tuition_id')->nullable();
            $table->foreign('tuition_id')->references('id')->on('tuitions')->onDelete('set null');

            // Status
            $table->enum('enrollment_status', ['Enrolled', 'Not Enrolled'])->default('Not Enrolled');
            $table->enum('payment_status', ['Paid', 'Not Paid'])->default('Not Paid')->nullable();

            // Soft deletes because the model uses SoftDeletes
            $table->softDeletes();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('students');
    }
};
