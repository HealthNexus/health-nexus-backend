<?php

namespace App\Notifications;

use App\Models\Post;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class NewDiseasePostCreatedNotification extends Notification implements ShouldQueue
{
    use Queueable;
    final const FRONT_END_POST_URL = 'http://localhost:9000/#/posts/';

    /**
     * Create a new notification instance.
     */
    public function __construct(protected Post $post) {}

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('New Disease Outbreak Alert!')
            ->line('Hello ' . $notifiable->name . ',')
            ->line('There is an outbreak of ' . $this->post->disease->name . ' in your area.')
            ->action('click here to learn more', self::FRONT_END_POST_URL  . $this->post->id)
            ->line('Thank you for using our application!');
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            //
        ];
    }
}
