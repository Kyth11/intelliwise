

<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('cor_headers', function (Blueprint $table) {
            $table->bigIncrements('id');

            // IMPORTANT: make this a char(12) to match students.lrn
            $table->char('student_id', 12);

            $table->unsignedBigInteger('guardian_id')->nullable();
            $table->string('school_year')->nullable();
            $table->string('semester')->nullable();
            $table->string('course_year')->nullable();
            $table->string('registration_no')->nullable();
            $table->string('cor_no')->nullable();
            $table->dateTime('date_enrolled')->nullable();
            $table->decimal('tuition_fee', 10, 2)->default(0);
            $table->decimal('misc_fee', 10, 2)->default(0);
            $table->decimal('other_fees', 10, 2)->default(0);
            $table->decimal('total_school_fees', 10, 2)->default(0);
            $table->string('signed_by_name')->nullable();
            $table->unsignedBigInteger('signed_by_user_id')->nullable();
            $table->longText('html_snapshot')->nullable();

            $table->timestamps();

            // FK to LRN, not ID
            $table->foreign('student_id')
                ->references('lrn')->on('students')
                ->onDelete('cascade');

            $table->foreign('guardian_id')
                ->references('id')->on('guardians')
                ->onDelete('set null');

            $table->foreign('signed_by_user_id')
                ->references('id')->on('users')
                ->onDelete('set null');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cor_headers');
    }
};
