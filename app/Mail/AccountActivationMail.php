<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class AccountActivationMail extends Mailable
{
    use Queueable, SerializesModels;

    /**
     * @var object|array
     */
    public $user;
    public string $setupUrl;

    /**
     * Create a new message instance.
     *
     * @param object|array $user
     * @param string $setupUrl
     */
    public function __construct($user, string $setupUrl)
    {
        $this->user = is_array($user) ? (object) $user : $user;
        $this->setupUrl = $setupUrl;
    }

    /**
     * Build the message.
     */
    public function build()
    {
        return $this->subject('Activate Your SmartClaim Account & Set Password (Valid 5 Mins)')
            ->view('emails.account-activation');
    }
}
