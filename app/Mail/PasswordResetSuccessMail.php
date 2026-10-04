<?php

namespace App\Mail;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class PasswordResetSuccessMail extends Mailable
{
    use Queueable, SerializesModels;

    /**
     * @var User|object
     */
    public $user;
    public string $loginUrl;
    public string $changedAt;

    /**
     * Create a new message instance.
     *
     * @param User|object $user
     * @param string|null $loginUrl
     */
    public function __construct($user, ?string $loginUrl = null)
    {
        $this->user = is_array($user) ? (object) $user : $user;
        $this->loginUrl = $loginUrl ?? url('/');
        $this->changedAt = now()->format('d M Y, h:i A T');
    }

    /**
     * Build the message.
     */
    public function build()
    {
        return $this->subject('Security Notification: Your SmartClaim Password Has Been Reset')
            ->view('emails.password-reset-success');
    }
}
