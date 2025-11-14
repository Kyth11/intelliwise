<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('payment_receipts', function (Blueprint $table) {
            $table->id();
            $table->char('student_id', 12)->index();
            $table->unsignedBigInteger('guardian_id')->nullable()->index();
            $table->unsignedBigInteger('payment_id')->nullable()->index(); // optional link to payments
            $table->decimal('amount', 10, 2);
            $table->string('reference_no')->nullable();
            $table->enum('method', ['G-cash', 'Cash', 'Other'])->default('G-cash');
            $table->string('image_path'); // stored file path
            $table->text('notes')->nullable();
            $table->enum('status', ['Pending', 'Approved', 'Rejected'])->default('Pending');
            $table->unsignedBigInteger('reviewed_by')->nullable();
            $table->timestamp('reviewed_at')->nullable();
            $table->timestamps();

            $table->foreign('student_id')->references('lrn')->on('students')->cascadeOnUpdate()->restrictOnDelete();
            $table->foreign('guardian_id')->references('id')->on('guardians')->cascadeOnUpdate()->nullOnDelete();
            $table->foreign('payment_id')->references('id')->on('payments')->cascadeOnUpdate()->nullOnDelete();
            $table->foreign('reviewed_by')->references('id')->on('users')->cascadeOnUpdate()->nullOnDelete();
        });
    }

};
