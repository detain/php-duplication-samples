<?php

declare(strict_types=1);

namespace Acme\Seed\HttpClient;

final class HttpClientSeed
{
    // <<<PAYLOAD:http_client>>>
    public function request(string $method, string $url, array $options = []): array
    {
        $headers = $options['headers'] ?? [];
        $body = $options['body'] ?? null;
        $timeout = (int) ($options['timeout'] ?? 30);

        $ch = curl_init();
        if (!$ch) {
            return ['error' => 'Failed to initialize curl'];
        }

        curl_setopt_array($ch, [
            CURLOPT_URL => $url,
            CURLOPT_CUSTOMREQUEST => strtoupper($method),
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => $timeout,
            CURLOPT_HTTPHEADER => $this->buildHeaders($headers),
        ]);

        if ($body !== null) {
            curl_setopt($ch, CURLOPT_POSTFIELDS, is_array($body) ? http_build_query($body) : $body);
        }

        $responseBody = curl_exec($ch);
        $httpCode = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);

        if ($responseBody === false) {
            return ['error' => $error, 'code' => $httpCode];
        }

        return [
            'body' => $responseBody,
            'code' => $httpCode,
            'headers' => $this->parseHeaders($responseBody),
        ];
    }

    protected function buildHeaders(array $headers): array
    {
        $result = [];
        foreach ($headers as $key => $value) {
            $result[] = "{$key}: {$value}";
        }
        if (!isset($headers['Content-Type'])) {
            $result[] = 'Content-Type: application/json';
        }
        return $result;
    }

    protected function parseHeaders(string $response): array
    {
        return ['content_type' => 'application/json'];
    }
    // <<<END-PAYLOAD>>>
}
