<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('schedules', function (Blueprint $table) {
            $table->id();

            $table->enum('day', [
                'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday'
            ]);

            // Store as TIME in MySQL; we'll input/validate as HH:MM in the app.
            $table->time('class_start');
            $table->time('class_end');

            $table->unsignedBigInteger('faculty_id');
            $table->unsignedBigInteger('subject_id');
            $table->unsignedBigInteger('room_id');

            // These are optional in your forms, so mark them nullable here:
            $table->unsignedBigInteger('gradelvl_id')->nullable();
            $table->unsignedBigInteger('section_id')->nullable();

            // String FK to schoolyrs.school_year (YYYY-YYYY), optional
            $table->string('school_year', 9)->collation('utf8mb4_unicode_ci')->nullable();

            // FKs
            $table->foreign('school_year')
                ->references('school_year')->on('schoolyrs')
                ->nullOnDelete()
                ->cascadeOnUpdate();

            $table->foreign('faculty_id')->references('id')->on('faculties')->cascadeOnDelete();
            $table->foreign('subject_id')->references('id')->on('subjects')->cascadeOnDelete();
            $table->foreign('room_id')->references('id')->on('rooms')->cascadeOnDelete();
            $table->foreign('gradelvl_id')->references('id')->on('gradelvls')->nullOnDelete();
            $table->foreign('section_id')->references('id')->on('sections')->nullOnDelete();

            $table->timestamps();

            // (Optional) indexes that help filters/sorts
            $table->index(['day', 'class_start']);
            $table->index(['school_year']);
        });

        // NOTE: Removed the extra Schema::table() from your original file.
        // It tried to add school_year again and wasn't needed.
    }

    public function down(): void
    {
        Schema::dropIfExists('schedules');
    }
};
