<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('subjects', function (Blueprint $table) {
            $table->id();
            $table->string('subject_name');              // e.g. "Math"
            $table->string('subject_code')->unique();    // e.g. "MATH1"
            $table->text('description')->nullable();

            $table->unsignedBigInteger('gradelvl_id');
            $table->foreign('gradelvl_id')
                ->references('id')
                ->on('gradelvls')
                ->cascadeOnDelete();

            $table->timestamps();
        });

        // ❌ Removed: no inserts / default seed values here.
    }

    public function down(): void
    {
        Schema::dropIfExists('subjects');
    }
};
