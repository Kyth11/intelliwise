<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('students', function (Blueprint $table) {
            $table->id();
            $table->string('s_firstname');
            $table->string('s_lastname');
            $table->string('s_middlename')->nullable();
            $table->date('s_birthdate')->nullable(); // YYYY-MM-DD format
            $table->string('s_address');
            $table->string('s_contact');
            $table->string('s_email')->nullable();
            $table->string('s_guardianfirstname');
            $table->string('s_guardianlastname');
            $table->string('s_guardiancontact');
            $table->string('s_guardianemail')->nullable();


            $table->string('s_gradelvl')->nullable();
            $table->unsignedBigInteger('gradelvl_id')->nullable();
            $table->foreign('gradelvl_id')->references('id')->on('gradelvl')->onDelete('set null')->onUpdate('cascade');

            $table->string('s_tuition_sum')->nullable();
            $table->unsignedBigInteger('tuition_id')->nullable();
            $table->foreign('tuition_id')->references('id')->on('tuition')->onDelete('set null')->onUpdate('cascade');


            $table->enum('enrollment_status', ['Enrolled', 'Not Enrolled'])->default('Not Enrolled');
            $table->enum('payment_status', ['Paid', 'Not Paid'])->default('Not Paid')->nullable();


            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('students');
    }
};
