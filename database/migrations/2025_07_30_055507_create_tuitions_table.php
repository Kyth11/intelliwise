<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('tuitions', function (Blueprint $table) {
            $table->id();

            // Grade level stored as text
            $table->string('grade_level');

            // Tuition values
            $table->decimal('monthly_fee', 10, 2)->default(0);
            $table->decimal('yearly_fee', 10, 2)->default(0);

            // Other fees
            $table->decimal('misc_fee', 10, 2)->nullable();
            $table->string('optional_fee_desc')->nullable();
            $table->decimal('optional_fee_amount', 10, 2)->default(0)->nullable();

            // Total
            $table->decimal('total_yearly', 10, 2)->default(0);

            // Optional School Year (FK to schoolyrs.id)
            $table->unsignedBigInteger('school_year')->nullable();
            $table->foreign('school_year')
                ->references('id')->on('schoolyrs')
                ->onDelete('cascade')->onUpdate('cascade');

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tuitions');
    }
};
