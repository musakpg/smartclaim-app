<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use NotificationChannels\WebPush\WebPushMessage;
use NotificationChannels\WebPush\WebPushChannel;

class WebPushNotification extends Notification
{
    use Queueable;

    public $title;
    public $message;
    public $actionUrl;

    public function __construct(string $title, string $message, ?string $actionUrl = null)
    {
        $this->title = $title;
        $this->message = $message;
        $this->actionUrl = $actionUrl ?? url('/dashboard');
    }

    public function via($notifiable)
    {
        return [WebPushChannel::class];
    }

    public function toWebPush($notifiable, $notification)
    {
        return (new WebPushMessage)
            ->title($this->title)
            ->body($this->message)
            ->icon('/favicon.ico')
            ->data([
                'url' => $this->actionUrl
            ])
            ->options([
                'TTL' => 86400,
                'urgency' => 'high',
            ]);
    }
}