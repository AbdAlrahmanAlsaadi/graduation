<?php

namespace App\Services\Notification;

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

        $token = trim((string) $user->fcm_token);

        if (empty($token)) {
            return;
        }

        try {
            $this->firebase->sendToToken(
                $token,
                $data['title'],
                $data['body'],
                array_merge($data['data'] ?? [], [
                    'notification_id' => $notification->id,
                    'type' => $data['type'],
                ])
            );
        } catch (\Throwable $e) {

            logger()->warning('FCM failed', [
                'user_id' => $user->id,
                'token'   => $token,
                'error'   => $e->getMessage(),
            ]);

            // 🚨 مهم: احذف التوكن إذا كان غير صالح
            if (
                str_contains($e->getMessage(), 'registration-token') ||
                str_contains($e->getMessage(), 'invalid')
            ) {
                $user->update(['fcm_token' => null]);
            }
        }
    }}
