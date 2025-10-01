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
        Schema::create('grades', function (Blueprint $table) {
            $table->id();
            $table->integer('quarter_grade');
            $table->integer(column: 'average_grade');
            $table->unsignedBigInteger('students_id');
            $table->unsignedBigInteger('subjects_id');
            $table->unsignedBigInteger('schoolyr_id');
            $table->unsignedBigInteger('gradelvl_id')->nullable();
            $table->unsignedBigInteger('faculty_id')->nullable();

            $table->foreign('faculty_id')->references('id')->on('faculties')->onDelete('set null')->onUpdate('cascade');
            $table->foreign('students_id')->references('id')->on('students')->onDelete('cascade')->onUpdate('cascade');
            $table->foreign('subjects_id')->references('id')->on('subjects')->onDelete('cascade')->onUpdate('cascade');
            $table->foreign('schoolyr_id')->references('id')->on('schoolyrs')->onDelete('cascade')->onUpdate('cascade');
            $table->foreign('gradelvl_id')->references('id')->on('gradelvls')->onDelete('set null')->onUpdate('cascade');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('grades');
    }
};
