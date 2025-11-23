<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('optional_fees', function (Blueprint $table) {
            $table->id();
            $table->string('name');                         // e.g., "PE Uniform", "ID", "Insurance"
            $table->decimal('amount', 10, 2)->default(0);
            $table->string('scope', 20)->default('both');   // grade | student | both
            $table->boolean('active')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('optional_fees');
    }
};
