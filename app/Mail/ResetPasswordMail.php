<?php

namespace App\Mail;

use Illuminate\Mail\Mailable;

class ResetPasswordMail extends Mailable
{
    public function __construct(public string $link) {}

    public function build()
    {
        return $this->subject('Reset Password')
            ->html("
                <p>Klik link di bawah untuk reset password:</p>
                <a href='{$this->link}'>Reset Password</a>
                <p>Link berlaku 30 menit</p>
            ");
    }
}
