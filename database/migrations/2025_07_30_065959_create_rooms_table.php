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
        Schema::create('rooms', function (Blueprint $table) {
            $table->id();
            $table->string('room_number');

            $table->unsignedBigInteger('faculty_id');
            $table->unsignedBigInteger('gradelvl_id');
            $table->unsignedBigInteger('schoolyr_id');
            $table->unsignedBigInteger('section_id');

            $table->foreign('section_id')->references('id')->on('sections')->onDelete('cascade')->onUpdate('cascade');
            $table->foreign('faculty_id')->references('id')->on('faculty')->onDelete('cascade')->onUpdate('cascade');
            $table->foreign('gradelvl_id')->references('id')->on('gradelvl')->onDelete('cascade')->onUpdate('cascade');
            $table->foreign('schoolyr_id')->references('id')->on('schoolyr')->onDelete('cascade')->onUpdate('cascade');

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('rooms');
    }
};
