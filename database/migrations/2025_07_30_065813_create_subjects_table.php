<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('subjects', function (Blueprint $table) {
            $table->id();
            $table->string('subject_name');

            $table->unsignedBigInteger('gradelvl_id');
            $table->foreign('gradelvl_id')
                ->references('id')
                ->on('gradelvls')
                ->cascadeOnDelete();

            $table->timestamps();
            $table->softDeletes();
        });
    }


    public function down(): void
    {
        Schema::dropIfExists('subjects');
    }
};
