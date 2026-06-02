<?php

declare(strict_types=1);

namespace App\Storage;

class FileStorage
{
    protected string $basePath;
    protected array $allowedExtensions = [];
    protected int $maxFileSize = 10485760;
    protected bool $generateUniqueNames = true;

    public function __construct(string $basePath, array $config = [])
    {
        $this->basePath = rtrim($basePath, '/');
        $this->allowedExtensions = $config['allowed_extensions'] ?? [];
        $this->maxFileSize = $config['max_file_size'] ?? 10485760;
        $this->generateUniqueNames = $config['generate_unique_names'] ?? true;
    }

    public function store(array $file, string $path = ''): ?string
    {
        if ($file['error'] !== UPLOAD_ERR_OK) {
            return null;
        }

        if (!$this->isAllowedSize($file['size'])) {
            return null;
        }

        if (!$this->isAllowedExtension($file['name'])) {
            return null;
        }

        $filename = $this->generateFilename($file['name'], $path);
        $fullPath = $this->getFullPath($filename);

        $directory = dirname($fullPath);
        if (!is_dir($directory)) {
            mkdir($directory, 0755, true);
        }

        if (!move_uploaded_file($file['tmp_name'], $fullPath)) {
            return null;
        }

        chmod($fullPath, 0644);

        return $filename;
    }

    public function delete(string $path): bool
    {
        $fullPath = $this->getFullPath($path);

        if (!file_exists($fullPath)) {
            return false;
        }

        return unlink($fullPath);
    }

    public function exists(string $path): bool
    {
        return file_exists($this->getFullPath($path));
    }

    public function getUrl(string $path): ?string
    {
        if (!$this->exists($path)) {
            return null;
        }

        return $this->basePath . '/' . ltrim($path, '/');
    }

    public function getContents(string $path): ?string
    {
        if (!$this->exists($path)) {
            return null;
        }

        return file_get_contents($this->getFullPath($path)) ?: null;
    }

    public function put(string $path, string $contents): bool
    {
        $fullPath = $this->getFullPath($path);
        $directory = dirname($fullPath);

        if (!is_dir($directory)) {
            mkdir($directory, 0755, true);
        }

        return file_put_contents($fullPath, $contents) !== false;
    }

    public function copy(string $source, string $destination): bool
    {
        if (!$this->exists($source)) {
            return false;
        }

        $destDir = dirname($this->getFullPath($destination));
        if (!is_dir($destDir)) {
            mkdir($destDir, 0755, true);
        }

        return copy($this->getFullPath($source), $this->getFullPath($destination));
    }

    public function move(string $source, string $destination): bool
    {
        if (!$this->exists($source)) {
            return false;
        }

        $destDir = dirname($this->getFullPath($destination));
        if (!is_dir($destDir)) {
            mkdir($destDir, 0755, true);
        }

        $fullSource = $this->getFullPath($source);
        $fullDest = $this->getFullPath($destination);

        if (!rename($fullSource, $fullDest)) {
            return false;
        }

        return true;
    }

    public function getSize(string $path): ?int
    {
        if (!$this->exists($path)) {
            return null;
        }

        return filesize($this->getFullPath($path)) ?: null;
    }

    public function getMimeType(string $path): ?string
    {
        if (!$this->exists($path)) {
            return null;
        }

        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mimeType = finfo_file($finfo, $this->getFullPath($path));
        finfo_close($finfo);

        return $mimeType ?: null;
    }

    public function listFiles(string $directory = '', bool $recursive = false): array
    {
        $fullPath = $this->getFullPath($directory);

        if (!is_dir($fullPath)) {
            return [];
        }

        $files = [];
        $iterator = $recursive
            ? new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($fullPath))
            : new \DirectoryIterator($fullPath);

        foreach ($iterator as $file) {
            if ($file->isFile()) {
                $relativePath = $file->getPathname();
                if (str_starts_with($relativePath, $this->basePath)) {
                    $relativePath = substr($relativePath, strlen($this->basePath) + 1);
                }
                $files[] = $relativePath;
            }
        }

        return $files;
    }

    protected function generateFilename(string $originalName, string $path): string
    {
        $extension = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));

        if ($this->generateUniqueNames) {
            $uniqueId = bin2hex(random_bytes(16));
            $safeExtension = preg_replace('/[^a-z0-9]/', '', $extension);
            $filename = $uniqueId . '.' . $safeExtension;
        } else {
            $safeName = preg_replace('/[^\w\.-]/', '_', pathinfo($originalName, PATHINFO_FILENAME));
            $safeExtension = preg_replace('/[^a-z0-9]/', '', $extension);
            $filename = $safeName . '.' . $safeExtension;
        }

        return ltrim($path . '/' . $filename, '/');
    }

    protected function getFullPath(string $path): string
    {
        return $this->basePath . '/' . ltrim($path, '/');
    }

    protected function isAllowedSize(int $size): bool
    {
        return $size <= $this->maxFileSize && $size > 0;
    }

    protected function isAllowedExtension(string $filename): bool
    {
        if (empty($this->allowedExtensions)) {
            return true;
        }

        $extension = strtolower(pathinfo($filename, PATHINFO_EXTENSION));

        return in_array($extension, $this->allowedExtensions, true);
    }
}
