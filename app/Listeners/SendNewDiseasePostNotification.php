<?php

namespace App\Listeners;

use App\Events\NewDiseasePostCreated;
use App\Models\User;
use App\Notifications\NewDiseasePostCreatedNotification;
use Illuminate\Contracts\Queue\ShouldQueue;

use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Notification;

class SendNewDiseasePostNotification implements ShouldQueue
{


    /**
     * Handle the event.
     */
    public function handle(NewDiseasePostCreated $event): void
    {
        $users = User::all();
        // Notification::send($users, new NewDiseasePostCreatedNotification($event->post));
        $delaySeconds = 0;
        foreach ($users as $user) {
            Notification::send($user, (new NewDiseasePostCreatedNotification($event->post))
                ->delay(now()->addSeconds($delaySeconds)));
            $delaySeconds += 2;
        }
    }
}
