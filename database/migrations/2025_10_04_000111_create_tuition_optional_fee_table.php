<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('tuition_optional_fee', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tuition_id')->constrained('tuitions')->cascadeOnDelete();
            $table->foreignId('optional_fee_id')->constrained('optional_fees')->cascadeOnDelete();
            $table->unique(['tuition_id', 'optional_fee_id']);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tuition_optional_fee');
    }
};
