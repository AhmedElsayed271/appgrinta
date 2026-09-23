<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class VerifiedEmail extends Mailable
{
    use Queueable, SerializesModels;

    public $token;
    public $clientName;
    public $local;

    /**
     * Create a new message instance.
     *
     * @return void
     */
    public function __construct($token, $clientName, $local)
    {
        $this->token = $token;
        $this->clientName = $clientName;
        $this->local = $local;
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {
        $subject = $this->local == 'ar' ? 'تفعيل حسابك في تطبيق جرينتا' : 'Activate your account';

        return $this->from(config('mail.from.address'), config('mail.from.name'))
            ->subject($subject)
            ->markdown('mails.verified')
            ->with([
                'token' => $this->token,
                'clientName' => $this->clientName,
                'local' => $this->local,
            ]);
    }
}
