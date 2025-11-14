<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('quarter_locks', function (Blueprint $table) {
            $table->id();
            // Single row controls access system-wide
            $table->string('scope', 16)->default('GLOBAL')->unique();
            // true = faculty may edit that quarter; false = locked
            $table->boolean('q1')->default(true);
            $table->boolean('q2')->default(true);
            $table->boolean('q3')->default(true);
            $table->boolean('q4')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('quarter_locks');
    }
};
