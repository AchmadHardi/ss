<?php

namespace App\Mail; 

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class VerifyEmailMail extends Mailable
{
    use Queueable, SerializesModels;

    public string $token;

    public function __construct(string $token)
    {
        $this->token = $token;
    }

    public function build()
    {
        return $this->subject('Verifikasi Email')
            ->view('emails.verify-email')
            ->with([
                'token' => $this->token
            ]);
    }
}
