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

            // FKs
            $table->unsignedBigInteger('student_id');
            $table->unsignedBigInteger('guardian_id')->nullable();
            $table->unsignedBigInteger('tuition_id')->nullable();
            $table->unsignedBigInteger('schoolyr_id');
            $table->unsignedBigInteger('gradelvl_id');
            $table->unsignedBigInteger('faculty_id')->nullable();

            // Dates
            $table->date('date_enrolled')->nullable();
            $table->date('date_dropped')->nullable(); // when enrollment was dropped

            // Meta
            $table->string('enrollment_type')->nullable(); // New/Transferee/Returnee
            $table->string('remarks')->nullable();
            $table->boolean('is_active')->default(true);

            // ---- Finance snapshot (cached at time of last calc) ----
            // You still can compute live from relations, but these keep the
            // reports fast and allow historical snapshotting by school year.
            $table->decimal('base_tuition',    12, 2)->default(0); // tuition->total_yearly
            $table->decimal('optional_total',  12, 2)->default(0); // selected optional fees
            $table->decimal('total_due',       12, 2)->default(0); // base + optional
            $table->decimal('paid_to_date',    12, 2)->default(0); // sum(payments.amount) by tuition_id
            $table->decimal('balance_cached',  12, 2)->default(0); // total_due - paid_to_date

            // Indexes / Constraints
            $table->unique(['student_id', 'schoolyr_id'], 'uq_student_per_sy');

            $table->foreign('student_id')->references('id')->on('students')->onDelete('cascade')->onUpdate('cascade');
            $table->foreign('guardian_id')->references('id')->on('guardians')->onDelete('set null')->onUpdate('cascade');
            $table->foreign('tuition_id')->references('id')->on('tuitions')->onDelete('set null')->onUpdate('cascade');
            $table->foreign('schoolyr_id')->references('id')->on('schoolyrs')->onDelete('cascade')->onUpdate('cascade');
            $table->foreign('gradelvl_id')->references('id')->on('gradelvls')->onDelete('cascade')->onUpdate('cascade');
            $table->foreign('faculty_id')->references('id')->on('faculties')->onDelete('set null')->onUpdate('cascade');

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('enrollment');
    }
};
