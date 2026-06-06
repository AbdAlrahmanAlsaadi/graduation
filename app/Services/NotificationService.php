<?php

namespace App\Services;

use App\Models\Notification;
use App\Models\User;

class NotificationService
{
    public function __construct(
        protected FirebaseService $firebase
    ) {}


    public function send(User $user, array $data): void
    {
        if (isset($data['sender_id']) && $data['sender_id'] === $user->id) {
            return;
        }


        $notification = Notification::create([
            'user_id' => $user->id,
            'project_id' => $data['project_id'] ?? null,
            'project_work_item_id' => $data['project_work_item_id'] ?? null,
            'type' => $data['type'],
            'title' => $data['title'],
            'body' => $data['body'],
            'data' => $data['data'] ?? null,
        ]);

        if (!$user->fcm_token) {
            return;
        }

        $this->firebase->sendToToken(
            $user->fcm_token,
            $data['title'],
            $data['body'],
            array_merge($data['data'] ?? [], [
                'notification_id' => $notification->id,
                'type' => $data['type'],
            ])
        );
    }
}
