<?php

namespace Kriegerhost\Notifications;

use Kriegerhost\Models\User;
use Illuminate\Notifications\Notification;
use Illuminate\Notifications\Messages\MailMessage;

class MailTested extends Notification
{
    /**
     * @var \Kriegerhost\Models\User
     */
    private $user;

    public function __construct(User $user)
    {
        $this->user = $user;
    }

    public function via()
    {
        return ['mail'];
    }

    public function toMail()
    {
        return (new MailMessage())
            ->subject('Kriegerhost Test Message')
            ->greeting('Hello ' . $this->user->name . '!')
            ->line('This is a test of the Kriegerhost mail system. You\'re good to go!');
    }
}
