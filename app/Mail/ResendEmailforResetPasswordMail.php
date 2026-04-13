<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class ResendEmailforResetPasswordMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public string $urlKey,
        public ?string $userName,
        public ?string $role,
        public string $userType,
    ) {
    }

    public function build()
    {
        $frontendUrl = rtrim((string) env('FRONTEND_URL', 'http://202.69.100.39:8080'), '/');
        $resetPasswordUrl = $frontendUrl.'/reset-password?key='.$this->urlKey.'&user_type='.$this->userType.'&role='.urlencode((string) $this->role);

        if ($this->userType === 'admin') {
            return $this->subject('Reset Password Akun Admin')
                ->view('emails.resend_email_admin')
                ->with([
                    'resetPasswordUrl' => $resetPasswordUrl,
                    'userName' => $this->userName,
                ]);
        }

        return $this->subject('Reset Password Akun Mitra')
            ->view('emails.resend_email_mitra')
            ->with([
                'resetPasswordUrl' => $resetPasswordUrl,
                'userName' => $this->userName,
            ]);
    }
}
