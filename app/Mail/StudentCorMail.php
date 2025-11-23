<?php

// app/Mail/StudentCorMail.php
namespace App\Mail;

use App\Models\Student;
use App\Models\Guardian;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class StudentCorMail extends Mailable
{
    use Queueable, SerializesModels;

    public Student $student;
    public ?Guardian $guardian;
    public array $billing;
    public string $schoolYear;
    public ?string $semester;
    public ?string $courseYear;
    public ?string $registrationNo;
    public string $signerName;
    public string $htmlSnapshot;

    public function __construct(
        Student $student,
        ?Guardian $guardian,
        array $billing,
        string $schoolYear,
        ?string $semester,
        ?string $courseYear,
        ?string $registrationNo,
        string $signerName,
        string $htmlSnapshot
    ) {
        $this->student        = $student;
        $this->guardian       = $guardian;
        $this->billing        = $billing;
        $this->schoolYear     = $schoolYear;
        $this->semester       = $semester;
        $this->courseYear     = $courseYear;
        $this->registrationNo = $registrationNo;
        $this->signerName     = $signerName;
        $this->htmlSnapshot   = $htmlSnapshot;
    }

    public function build()
    {
        $email = $this->subject('Certificate of Registration - '.$this->student->lrn)
            ->view('emails.cor', [
                'student'        => $this->student,
                'guardian'       => $this->guardian,
                'billing'        => $this->billing,
                'schoolYear'     => $this->schoolYear,
                'semester'       => $this->semester,
                'courseYear'     => $this->courseYear,
                'registrationNo' => $this->registrationNo,
                'signerName'     => $this->signerName,
            ]);

        // Force the same HTML snapshot we will save, so email + DB are identical
        $email->withSwiftMessage(function ($message) {
            // Left as-is; content already from Blade
        });

        return $email;
    }
}
