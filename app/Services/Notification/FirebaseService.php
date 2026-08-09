<?php

namespace App\Services\Notification;

use Kreait\Firebase\Factory;
use Kreait\Firebase\Messaging\CloudMessage;
use Kreait\Firebase\Messaging\Notification;

class FirebaseService
{
    protected $messaging;

    public function __construct()
    {
        $this->messaging = (new Factory)
            ->withServiceAccount(storage_path('app\graduation-9d9e5-firebase-adminsdk-fbsvc-8b4d2fecf7.json'))
            ->createMessaging();
    }


    public function sendToToken(string $token, string $title, string $body, array $data = []): void
    {
        $message = CloudMessage::withTarget('token', $token)
            ->withNotification(
                Notification::create($title, $body)
            )
            ->withData($this->formatData($data));

        $this->messaging->send($message);
    }


    public function sendToTopic(string $topic, string $title, string $body, array $data = []): void
    {
        $message = CloudMessage::withTarget('topic', $topic)
            ->withNotification(
                Notification::create($title, $body)
            )
            ->withData($this->formatData($data));

        $this->messaging->send($message);
    }


    private function formatData(array $data): array
    {
        return array_map(fn($value) => (string) $value, $data);
    }
}
