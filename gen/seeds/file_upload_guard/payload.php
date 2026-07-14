<?php

declare(strict_types=1);

namespace Acme\Seed\FileUploadGuard;

/**
 * Seed payload: validate file upload against size, extension, and MIME type.
 */
final class FileUploadGuardSeed
{
    // <<<PAYLOAD:file_upload_guard>>>
    public function validateUpload(array $file, array $rules): array
    {
        $errors = [];
        if ($file['error'] !== UPLOAD_ERR_OK) {
            $errors[] = 'upload_error';
            return ['valid' => false, 'errors' => $errors];
        }
        if (isset($rules['max_size']) && $file['size'] > $rules['max_size']) {
            $errors[] = 'too_large';
        }
        $allowedExts = $rules['allowed_extensions'] ?? [];
        if (!empty($allowedExts)) {
            $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
            if (!in_array($ext, $allowedExts, true)) {
                $errors[] = 'invalid_extension';
            }
        }
        $allowedTypes = $rules['allowed_types'] ?? [];
        if (!empty($allowedTypes) && !in_array($file['type'], $allowedTypes, true)) {
            $errors[] = 'invalid_type';
        }
        return ['valid' => empty($errors), 'errors' => $errors];
    }
    // <<<END-PAYLOAD>>>
}
