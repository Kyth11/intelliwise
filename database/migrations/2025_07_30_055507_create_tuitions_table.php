<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('tuitions', function (Blueprint $table) {
            $table->id();

            // Basic identification
            $table->string('grade_level');

            // Tuition (split)
            $table->decimal('tuition_monthly', 10, 2)->default(0);
            $table->decimal('tuition_yearly', 10, 2)->default(0);  // ~= monthly * 10

            // Misc fees (split)
            $table->decimal('misc_monthly', 10, 2)->nullable();
            $table->decimal('misc_yearly', 10, 2)->nullable();

            // Books
            $table->string('books_desc')->nullable();
            $table->decimal('books_amount', 10, 2)->nullable();

            // Enrollment / registration fee (this is what the Blade calls "Enrollment Fee")
            $table->decimal('registration_fee', 10, 2)->nullable();

            // Computed total (tuition_yearly + misc_yearly + books_amount + registration_fee + grade-level optional fees)
            $table->decimal('total_yearly', 10, 2)->default(0);

            // School year text (for display / simple select) e.g. "2025-2026"
            $table->string('school_year', 9)->nullable();

            // Normalized FK to schoolyrs (optional, matches your model)
            $table->unsignedBigInteger('schoolyr_id')->nullable();

            $table->timestamps();
            $table->softDeletes();

            $table->index('grade_level');
            $table->index('school_year');

            $table->foreign('schoolyr_id')
                ->references('id')
                ->on('schoolyrs')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tuitions');
    }
};
