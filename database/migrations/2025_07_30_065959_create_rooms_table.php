<?php

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
Schema::create('rooms', function (Blueprint $table) {
    $table->id();
    $table->string('room_number'); // e.g., "101"
    $table->string('building')->nullable();
    $table->timestamps();
});
    DB::table('rooms')->insert([
        ['room_number' => '101', 'building' => 'Main'],
    ]);

    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('rooms');
    }
};
