<?php

namespace App\Mail;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class AccountActivatedConfirmationMail extends Mailable
{
    use Queueable, SerializesModels;

    /**
     * @var User|object
     */
    public $user;
    public string $loginUrl;

    /**
     * Create a new message instance.
     *
     * @param User|object $user
     * @param string $loginUrl
     */
    public function __construct($user, string $loginUrl)
    {
        $this->user = is_array($user) ? (object) $user : $user;
        $this->loginUrl = $loginUrl;
    }

    /**
     * Build the message.
     */
    public function build()
    {
        return $this->subject('Welcome to SmartClaim - Your Account is Now Active!')
            ->view('emails.account-activated');
    }
}
