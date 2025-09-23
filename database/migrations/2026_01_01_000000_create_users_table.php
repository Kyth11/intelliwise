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
        Schema::create('users', function (Blueprint $table) {
    $table->id();
    $table->string('name');
    $table->string('username')->unique();
    $table->string('password');
    $table->enum('role', ['admin', 'faculty', 'guardian']);

    $table->unsignedBigInteger('faculty_id')->nullable();
    $table->unsignedBigInteger('guardian_id')->nullable();
    $table->foreign('faculty_id')->references('id')->on('faculty')->onDelete('set null')->onUpdate('cascade');
    $table->foreign('guardian_id')->references('id')->on('guardian')->onDelete('set null')->onUpdate('cascade');
    $table->timestamps();
        });

        DB::table('users')->insert([
            [
                'name' => 'AdminDefault',
                'username' => 'admin',
                'password' => bcrypt('admin123'), // Hash the password
                'role' => 'admin',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'FacultyDefault',
                'username' => 'faculty',
                'password' => bcrypt('faculty123'),
                'role' => 'faculty',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'GuardianDefault',
                'username' => 'guardian',
                'password' => bcrypt('guardian123'),
                'role' => 'guardian',
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);

    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('users');

    }
};
