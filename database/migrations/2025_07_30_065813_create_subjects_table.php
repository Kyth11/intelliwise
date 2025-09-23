<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('subjects', function (Blueprint $table) {
            $table->id();
            $table->string('subject_name');
            $table->string('subject_code')->unique();

            $table->unsignedBigInteger('faculty_id')->nullable();
            $table->unsignedBigInteger('gradelvl_id')->nullable();
            $table->unsignedBigInteger('schoolyr_id')->nullable();
            $table->foreign('faculty_id')->references('id')->on('faculty')->onDelete('set null')->onUpdate('cascade');
            $table->foreign('gradelvl_id')->references('id')->on('gradelvl')->onDelete('set null')->onUpdate('cascade');
            $table->foreign('schoolyr_id')->references('id')->on('schoolyr')->onDelete('set null')->onUpdate('cascade');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('subjects');
    }
};
