<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class UserVerificationMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public string $urlKey,
        public ?string $userName,
        public string $userType,
        public ?string $role,
    ) {
    }

    public function build()
    {
        $frontendUrl = rtrim((string) env('FRONTEND_URL', 'http://localhost:5173'), '/');
        $verificationUrl = $frontendUrl.'/reset-password?key='.$this->urlKey.'&user_type='.$this->userType.'&role='.urlencode((string) $this->role);

        return $this->subject('Verifikasi Akun Pengguna')
            ->view('emails.user_verification')
            ->with([
                'verificationUrl' => $verificationUrl,
                'userName' => $this->userName,
            ]);
    }
}
