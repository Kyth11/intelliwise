<?php

use Illuminate\Support\Facades\DB;
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
Schema::create('subjects', function (Blueprint $table) {
    $table->id();
    $table->string('subject_name'); // "Math"
    $table->string('subject_code')->unique(); // "MATH1"
    $table->text('description')->nullable();

    $table->unsignedBigInteger('gradelvl_id');
    $table->foreign('gradelvl_id')->references('id')->on('gradelvls')->onDelete('cascade');

    $table->timestamps();

});
    DB::table('subjects')->insert([
        ['subject_name' => 'Math', 'subject_code' => 'MATH1', 'description' => 'Mathematics', 'gradelvl_id' => 4],
        ['subject_name' => 'Science', 'subject_code' => 'SCI1', 'description' => 'Science', 'gradelvl_id' => 4],
        ['subject_name' => 'English', 'subject_code' => 'ENG1', 'description' => 'English', 'gradelvl_id' => 4],
        ['subject_name' => 'History', 'subject_code' => 'HIST1', 'description' => 'History', 'gradelvl_id' => 4]

    ]);


    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('subjects');
    }
};
