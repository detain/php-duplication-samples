<?php

declare(strict_types=1);

namespace Acme\Storage\Metrics;

final class StorageMetrics
{
    public function upload(string $localPath, string $destination, array $options = []): array
    {
        if (!is_file($localPath)) {
            return ['success' => false, 'error' => 'Source file not found'];
        }

        $visibility = $options['visibility'] ?? 'private';
        $contentType = $options['content_type'] ?? 'application/octet-stream';
        $metadata = $options['metadata'] ?? [];

        $filename = basename($destination);
        $dir = dirname($destination);
        if ($dir !== '' && $dir !== '.') {
            $this->ensureDirectory($dir);
        }

        $size = filesize($localPath);
        $hash = hash_file('sha256', $localPath);

        $stored = $this->storeFile($localPath, $destination, [
            'visibility' => $visibility,
            'content_type' => $contentType,
            'size' => $size,
            'hash' => $hash,
            'metadata' => $metadata,
        ]);

        if (!$stored) {
            return ['success' => false, 'error' => 'Storage operation failed'];
        }

        return [
            'success' => true,
            'path' => $destination,
            'url' => $this->getUrl($destination, $visibility),
            'size' => $size,
            'hash' => $hash,
        ];
    }

    public function download(string $path, ?string $localPath = null): array
    {
        if ($localPath === null) {
            $localPath = sys_get_temp_dir() . '/' . basename($path);
        }

        $retrieved = $this->retrieveFile($path, $localPath);
        if (!$retrieved) {
            return ['success' => false, 'error' => 'File not found or retrieval failed'];
        }

        return [
            'success' => true,
            'path' => $localPath,
            'size' => filesize($localPath),
        ];
    }

    public function delete(string $path): bool
    {
        return $this->removeFile($path);
    }

    public function exists(string $path): bool
    {
        return $this->fileExists($path);
    }

    public function url(string $path, int $expires = 3600): ?string
    {
        if (!$this->exists($path)) {
            return null;
        }
        return $this->getSignedUrl($path, $expires);
    }

    protected function ensureDirectory(string $path): void
    {
    }

    protected function storeFile(string $source, string $dest, array $opts): bool
    {
        return copy($source, $dest);
    }

    protected function retrieveFile(string $path, string $localPath): bool
    {
        return copy($path, $localPath);
    }

    protected function removeFile(string $path): bool
    {
        if (is_file($path)) {
            return unlink($path);
        }
        return false;
    }

    protected function fileExists(string $path): bool
    {
        return is_file($path);
    }

    protected function getUrl(string $path, string $visibility): string
    {
        if ($visibility === 'public') {
            return '/storage/' . ltrim($path, '/');
        }
        return $this->getSignedUrl($path, 3600);
    }

    protected function getSignedUrl(string $path, int $expires): string
    {
        $expiry = time() + $expires;
        $signature = hash_hmac('sha256', "{$path}:{$expiry}", 'secret');
        return "/storage/{$path}?expires={$expiry}&signature={$signature}";
    }
}
