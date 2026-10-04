<?php

namespace App\Mail;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class SystemNotificationMail extends Mailable
{
    use Queueable, SerializesModels;

    /**
     * @var User|object
     */
    public $user;
    public string $notificationTitle;
    public string $notificationMessage;
    public string $notificationType;
    public ?string $actionUrl;

    /**
     * Create a new message instance.
     *
     * @param User|object $user
     * @param string $notificationTitle
     * @param string $notificationMessage
     * @param string $notificationType
     * @param string|null $actionUrl
     */
    public function __construct($user, string $notificationTitle, string $notificationMessage, string $notificationType = 'info', ?string $actionUrl = null)
    {
        $this->user = is_array($user) ? (object) $user : $user;
        $this->notificationTitle = $notificationTitle;
        $this->notificationMessage = $notificationMessage;
        $this->notificationType = strtolower($notificationType);
        $this->actionUrl = $actionUrl;
    }

    /**
     * Build the message.
     */
    public function build()
    {
        return $this->subject("[SmartClaim] {$this->notificationTitle}")
            ->view('emails.system-notification');
    }
}
