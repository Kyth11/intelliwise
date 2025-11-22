<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class FacultyCredentialsMail extends Mailable
{
    use Queueable, SerializesModels;

    public $faculty;
    public $username;
    public $plainPassword;

    /**
     * @param  \App\Models\Faculty  $faculty
     * @param  string  $username
     * @param  string  $plainPassword
     */
    public function __construct($faculty, string $username, string $plainPassword)
    {
        $this->faculty       = $faculty;
        $this->username      = $username;
        $this->plainPassword = $plainPassword;
    }

    public function build()
    {
        return $this->subject('Your Faculty Account Credentials')
                    ->view('emails.faculty-credentials');
    }
}
