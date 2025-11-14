<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('announcement_gradelvl', function (Blueprint $table) {
            $table->id();
            $table->foreignId('announcement_id')
                ->constrained('announcements')
                ->cascadeOnDelete();
            $table->foreignId('gradelvl_id')
                ->constrained('gradelvls')
                ->cascadeOnDelete();
            $table->unique(['announcement_id', 'gradelvl_id']);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('announcement_gradelvl');
    }
};
