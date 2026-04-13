<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class RegisterSuccessMitraMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public ?string $userName,
        public string $userId,
    ) {
    }

    public function build()
    {
        return $this->subject('Registrasi Akun Mitra Berhasil')
            ->view('emails.register_success')
            ->with([
                'userName' => $this->userName,
                'userId' => $this->userId,
            ]);
    }
}
