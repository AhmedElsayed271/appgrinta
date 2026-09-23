<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class SendTokenToEmail extends Mailable
{
    use Queueable, SerializesModels;

    protected $token;
    protected $clientName;
    /**
     * Create a new message instance.
     *
     * @return void
     */
    public function __construct($token,$clientName)
    {
        $this->token=$token;
        $this->clientName=$clientName;
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build(): SendTokenToEmail
    {
        return $this->from('matches@grintapp.com')
            ->subject('Send Client\'s Code')
            ->markdown('mails.token')
            ->with([
                'token' => $this->token,
                'clientName' => $this->clientName,
            ]);
    }
}
