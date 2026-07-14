<?php

declare(strict_types=1);

namespace Acme\Seed\NotificationService;

final class NotificationServiceSeed
{
    // <<<PAYLOAD:notification_service>>>
    public function send(string $channel, string $recipient, string $template, array $data = []): array
    {
        $rendered = $this->render($template, $data);

        return match ($channel) {
            'email' => $this->sendEmail($recipient, $rendered['subject'], $rendered['body']),
            'sms' => $this->sendSms($recipient, $rendered['body']),
            'push' => $this->sendPush($recipient, $rendered['title'], $rendered['body']),
            default => ['success' => false, 'error' => 'Unknown channel'],
        };
    }

    public function sendBatch(string $channel, array $recipients, string $template, array $data = []): array
    {
        $results = [];
        foreach ($recipients as $recipient) {
            $results[$recipient] = $this->send($channel, $recipient, $template, $data);
        }
        $sent = count(array_filter($results, fn($r) => $r['success'] ?? false));
        return [
            'total' => count($recipients),
            'sent' => $sent,
            'failed' => count($recipients) - $sent,
            'results' => $results,
        ];
    }

    private function render(string $template, array $data): array
    {
        $subject = $data['subject'] ?? '';
        $body = preg_replace_callback('/\{\{(\w+)\}\}/', function ($m) use ($data) {
            return $data[$m[1]] ?? $m[0];
        }, $template);
        return ['subject' => $subject, 'body' => $body];
    }

    private function sendEmail(string $to, string $subject, string $body): array
    {
        if (!filter_var($to, FILTER_VALIDATE_EMAIL)) {
            return ['success' => false, 'error' => 'Invalid email address'];
        }
        return ['success' => true, 'message_id' => 'em_' . bin2hex(random_bytes(8))];
    }

    private function sendSms(string $to, string $body): array
    {
        if (!preg_match('/^\+?[1-9]\d{6,14}$/', $to)) {
            return ['success' => false, 'error' => 'Invalid phone number'];
        }
        return ['success' => true, 'message_id' => 'sms_' . bin2hex(random_bytes(8))];
    }

    private function sendPush(string $token, string $title, string $body): array
    {
        if (strlen($token) < 10) {
            return ['success' => false, 'error' => 'Invalid push token'];
        }
        return ['success' => true, 'message_id' => 'push_' . bin2hex(random_bytes(8))];
    }
    // <<<END-PAYLOAD>>>
}
