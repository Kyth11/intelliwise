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
            $table->string('name'); // Full name
            $table->string('username')->unique(); // For login
            $table->string('password');
            $table->enum('role', ['admin', 'faculty', 'guardian']); // Role check

            // Foreign keys to faculty and guardian tables
            $table->unsignedBigInteger('faculty_id')->nullable();
            $table->unsignedBigInteger('guardian_id')->nullable();

            $table->foreign('faculty_id')
                  ->references('id')
                  ->on('faculties')
                  ->onDelete('set null')
                  ->onUpdate('cascade');

            $table->foreign('guardian_id')
                  ->references('id')
                  ->on('guardians')
                  ->onDelete('set null')
                  ->onUpdate('cascade');

            $table->timestamps();
        });

        // Insert default accounts
        DB::table('users')->insert([
            [
                'name' => 'Default Admin',
                'username' => 'admin',
                'password' => bcrypt('admin123'), // Hashed password
                'role' => 'admin',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Default Faculty',
                'username' => 'faculty',
                'password' => bcrypt('faculty123'),
                'role' => 'faculty',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Default Guardian',
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
