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
        Schema::create('schoolyr', function (Blueprint $table) {
            $table->id();
            $table->string('school_year'); // Added column for school year
            $table->date('start_date'); // Added column for start date
            $table->date('end_date'); // Added column for end date
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('schoolyr');
    }
};
