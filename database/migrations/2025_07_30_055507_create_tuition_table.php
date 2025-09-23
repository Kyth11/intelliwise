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
        Schema::create('tuition', function (Blueprint $table) {
            $table->id();
            $table->string('tuition_fee');
            $table->string('misc_fee');
            $table->string('total');
            $table->unsignedBigInteger('schoolyr_id');
            $table->foreign(columns: 'schoolyr_id')->references('id')->on(table: 'schoolyr')->onDelete('cascade')->onUpdate('cascade');

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tuition');
    }
};
