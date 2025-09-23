<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('enrollment', function (Blueprint $table) {
            $table->id();
            $table->enum('status', ['Enrolled', 'Not Enrolled'])->default('Not Enrolled');
            $table->unsignedBigInteger('student_id');
            $table->unsignedBigInteger('schoolyr_id');
            $table->unsignedBigInteger('gradelvl_id');
            $table->date('date_enrolled')->nullable();
            $table->date('date_dropped')->nullable(); // Date when enrollment was dropped
            $table->string('enrollment_type')->nullable(); // e.g. 'New', 'Transferee', 'Returnee'
            $table->string('remarks')->nullable(); // Additional notes
            $table->boolean('is_active')->default(true); // Active/inactive flag
            $table->unsignedBigInteger('section_id')->nullable(); // Section assignment
            $table->unsignedBigInteger('faculty_id')->nullable();

            $table->foreign('student_id')->references('id')->on('students')->onDelete('cascade')->onUpdate('cascade');
            $table->foreign('schoolyr_id')->references('id')->on('schoolyr')->onDelete('cascade')->onUpdate('cascade');
            $table->foreign('gradelvl_id')->references('id')->on('gradelvl')->onDelete('cascade')->onUpdate('cascade');
            $table->foreign('section_id')->references('id')->on('sections')->onDelete('set null')->onUpdate('cascade');
            $table->foreign('faculty_id')->references('id')->on('faculty')->onDelete('set null')->onUpdate('cascade');

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('enrollment');
    }
};
