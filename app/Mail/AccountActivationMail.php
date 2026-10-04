<?php

namespace App\Mail;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class AccountActivationMail extends Mailable
{
    use Queueable, SerializesModels;

    public User $user;
    public string $setupUrl;

    /**
     * Create a new message instance.
     */
    public function __construct(User $user, string $setupUrl)
    {
        $this->user = $user;
        $this->setupUrl = $setupUrl;
    }

    /**
     * Build the message.
     */
    public function build()
    {
        return $this->subject('Activate Your SmartClaim Account & Set Password')
            ->view('emails.account-activation');
    }
}
