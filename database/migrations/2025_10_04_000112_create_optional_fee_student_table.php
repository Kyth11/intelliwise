<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('optional_fee_student', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('optional_fee_id');
            $table->unsignedBigInteger('student_id');
            $table->timestamps();

            $table->foreign('optional_fee_id')->references('id')->on('optional_fees')->onDelete('cascade');
            $table->foreign('student_id')->references('id')->on('students')->onDelete('cascade');

            $table->unique(['optional_fee_id', 'student_id'], 'optional_fee_student_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('optional_fee_student');
    }
};
