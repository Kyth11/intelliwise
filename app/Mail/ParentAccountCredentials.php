<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class ParentAccountCredentials extends Mailable
{
    use Queueable, SerializesModels;

    public string $guardianName;
    public string $studentName;
    public string $username;
    public string $password;
    public string $appUrl;

    public function __construct(string $guardianName, string $studentName, string $username, string $password, string $appUrl)
    {
        $this->guardianName = $guardianName;
        $this->studentName  = $studentName;
        $this->username     = $username;
        $this->password     = $password;
        $this->appUrl       = $appUrl;
    }

    public function build()
    {
        return $this->subject('Your Parent/Guardian Account Credentials')
            ->view('emails.parent_credentials');     // HTML view
            // ->text('emails.parent_credentials_plain'); // Plain-text fallback
    }
}
