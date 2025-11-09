<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('grades', function (Blueprint $table) {
            $table->id();

            // Per-quarter grades (0–100)
            $table->unsignedTinyInteger('q1')->nullable();
            $table->unsignedTinyInteger('q2')->nullable();
            $table->unsignedTinyInteger('q3')->nullable();
            $table->unsignedTinyInteger('q4')->nullable();

            // Final grade + DepEd remark
            $table->unsignedTinyInteger('final_grade')->nullable();
            $table->string('remark', 32)->nullable(); // PASSED / FAILED

            // Conventional FK names
            $table->char('student_id', 12);
            $table->foreign('student_id')->references('lrn')->on('students')->cascadeOnDelete()->cascadeOnUpdate()->cascadeOnDelete()->cascadeOnUpdate();
            $table->foreignId('subject_id')->constrained('subjects')->cascadeOnDelete()->cascadeOnUpdate();
            $table->foreignId('schoolyr_id')->constrained('schoolyrs')->cascadeOnDelete()->cascadeOnUpdate();
            $table->foreignId('gradelvl_id')->nullable()->constrained('gradelvls')->nullOnDelete()->cascadeOnUpdate();
            $table->foreignId('faculty_id')->nullable()->constrained('faculties')->nullOnDelete()->cascadeOnUpdate();

            // Prevent duplicate grade rows per (student, subject, school year)
            $table->unique(['student_id', 'subject_id', 'schoolyr_id'], 'grade_unique');

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('grades');
    }
};
