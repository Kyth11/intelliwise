<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('enrollment', function (Blueprint $table) {
            $table->id();

            // Core status
            $table->enum('status', ['Enrolled', 'Not Enrolled'])->default('Not Enrolled');
            $table->enum('payment_status', ['Paid', 'Partial', 'Not Paid'])->default('Not Paid');

            // FKs (match Student PK = CHAR(12))
            $table->char('student_id', 12);                  // <— was unsignedBigInteger
            $table->unsignedBigInteger('guardian_id')->nullable();
            $table->unsignedBigInteger('tuition_id')->nullable();
            $table->unsignedBigInteger('schoolyr_id');
            $table->unsignedBigInteger('gradelvl_id');
            $table->unsignedBigInteger('faculty_id')->nullable();

            // Dates
            $table->date('date_enrolled')->nullable();
            $table->date('date_dropped')->nullable();

            // Meta
            $table->string('enrollment_type')->nullable(); // New/Transferee/Returnee
            $table->string('remarks')->nullable();
            $table->boolean('is_active')->default(true);

            // Finance snapshot
            $table->decimal('base_tuition',   12, 2)->default(0);
            $table->decimal('optional_total', 12, 2)->default(0);
            $table->decimal('total_due',      12, 2)->default(0);
            $table->decimal('paid_to_date',   12, 2)->default(0);
            $table->decimal('balance_cached', 12, 2)->default(0);

            // Indexes / Constraints
            $table->unique(['student_id', 'schoolyr_id'], 'uq_student_per_sy');

            // Foreign keys
            $table->foreign('student_id')
                  ->references('lrn')->on('students')
                  ->cascadeOnDelete()->cascadeOnUpdate();

            $table->foreign('guardian_id')->references('id')->on('guardians')->nullOnDelete()->cascadeOnUpdate();
            $table->foreign('tuition_id')->references('id')->on('tuitions')->nullOnDelete()->cascadeOnUpdate();
            $table->foreign('schoolyr_id')->references('id')->on('schoolyrs')->cascadeOnDelete()->cascadeOnUpdate();
            $table->foreign('gradelvl_id')->references('id')->on('gradelvls')->cascadeOnDelete()->cascadeOnUpdate();
            $table->foreign('faculty_id')->references('id')->on('faculties')->nullOnDelete()->cascadeOnUpdate();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('enrollment');
    }
};
