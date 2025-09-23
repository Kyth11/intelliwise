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
        Schema::create('schedules', function (Blueprint $table) {
            $table->id();
            $table->string('day'); // Column for day of the week
            $table->time('class_start')->nullable(); // Column for start time
            $table->time('class_end')->nullable(); // Column for end time
            $table->unsignedBigInteger('faculty_id')->nullable();
            $table->unsignedBigInteger('gradelvl_id')->nullable();
            $table->unsignedBigInteger('schoolyr_id')->nullable();
            $table->unsignedBigInteger('subject_id')->nullable();
            $table->unsignedBigInteger('room_id')->nullable();
            $table->unsignedBigInteger('students_id')->nullable();
            $table->unsignedBigInteger('section_id')->nullable();
            
            $table->foreign('section_id')->references('id')->on('sections')->onDelete('set null')->onUpdate('cascade');
            $table->foreign('students_id')->references('id')->on('students')->onDelete('set null')->onUpdate('cascade');
            $table->foreign('faculty_id')->references('id')->on('faculty')->onDelete('set null')->onUpdate('cascade');
            $table->foreign('gradelvl_id')->references('id')->on('gradelvl')->onDelete('set null')->onUpdate('cascade');
            $table->foreign('schoolyr_id')->references('id')->on('schoolyr')->onDelete('set null')->onUpdate('cascade');
            $table->foreign('subject_id')->references('id')->on('subjects')->onDelete('set null')->onUpdate('cascade');
            $table->foreign('room_id')->references('id')->on('rooms')->onDelete('set null')->onUpdate('cascade');

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('schedules');
    }
};
