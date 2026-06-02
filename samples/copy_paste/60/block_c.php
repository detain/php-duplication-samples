<?php

declare(strict_types=1);

namespace App\Images;

class ImageProcessor
{
    protected int $maxWidth = 4096;
    protected int $maxHeight = 4096;
    protected int $quality = 85;
    protected array $allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];

    public function __construct(array $config = [])
    {
        $this->maxWidth = $config['max_width'] ?? 4096;
        $this->maxHeight = $config['max_height'] ?? 4096;
        $this->quality = $config['quality'] ?? 85;
        $this->allowedTypes = $config['allowed_types'] ?? $this->allowedTypes;
    }

    public function process(string $inputPath, string $outputPath, array $options = []): bool
    {
        if (!file_exists($inputPath)) {
            return false;
        }

        $imageInfo = getimagesize($inputPath);
        if ($imageInfo === false) {
            return false;
        }

        $mimeType = $imageInfo['mime'];
        if (!in_array($mimeType, $this->allowedTypes, true)) {
            return false;
        }

        $width = $options['width'] ?? null;
        $height = $options['height'] ?? null;
        $maintainAspectRatio = $options['maintain_aspect_ratio'] ?? true;
        $forceResize = $options['force_resize'] ?? false;

        $sourceWidth = $imageInfo[0];
        $sourceHeight = $imageInfo[1];

        if ($width === null && $height === null) {
            if (!$forceResize && $sourceWidth <= $this->maxWidth && $sourceHeight <= $this->maxHeight) {
                return copy($inputPath, $outputPath);
            }

            $width = $this->maxWidth;
            $height = $this->maxHeight;
            $maintainAspectRatio = true;
        }

        if ($maintainAspectRatio) {
            [$width, $height] = $this->calculateDimensions(
                $sourceWidth,
                $sourceHeight,
                $width,
                $height
            );
        }

        return $this->resizeImage($inputPath, $outputPath, $width, $height, $mimeType);
    }

    protected function calculateDimensions(int $srcW, int $srcH, ?int $targetW, ?int $targetH): array
    {
        if ($targetW === null && $targetH === null) {
            return [$srcW, $srcH];
        }

        if ($targetW === null) {
            $ratio = $targetH / $srcH;
            return [(int) round($srcW * $ratio), $targetH];
        }

        if ($targetH === null) {
            $ratio = $targetW / $srcW;
            return [$targetW, (int) round($srcH * $ratio)];
        }

        $ratioW = $targetW / $srcW;
        $ratioH = $targetH / $srcH;
        $ratio = min($ratioW, $ratioH);

        return [
            (int) round($srcW * $ratio),
            (int) round($srcH * $ratio),
        ];
    }

    protected function resizeImage(
        string $inputPath,
        string $outputPath,
        int $width,
        int $height,
        string $mimeType
    ): bool {
        $sourceImage = $this->createImageFromFile($inputPath, $mimeType);

        if ($sourceImage === null) {
            return false;
        }

        $resizedImage = imagecreatetruecolor($width, $height);

        if ($mimeType === 'image/png') {
            imagealphablending($resizedImage, false);
            imagesavealpha($resizedImage, true);
        } elseif ($mimeType === 'image/gif') {
            $transparentIndex = imagecolortransparent($sourceImage);
            if ($transparentIndex >= 0) {
                $transparentColor = imagecolorsforindex($sourceImage, $transparentIndex);
                $transparentNew = imagecolorallocate(
                    $resizedImage,
                    $transparentColor['red'],
                    $transparentColor['green'],
                    $transparentColor['blue']
                );
                imagefill($resizedImage, 0, 0, $transparentNew);
                imagecolortransparent($resizedImage, $transparentNew);
            }
        }

        imagecopyresampled(
            $resizedImage,
            $sourceImage,
            0, 0, 0, 0,
            $width,
            $height,
            imagesx($sourceImage),
            imagesy($sourceImage)
        );

        $success = $this->saveImage($resizedImage, $outputPath, $mimeType);

        imagedestroy($sourceImage);
        imagedestroy($resizedImage);

        return $success;
    }

    protected function createImageFromFile(string $path, string $mimeType)
    {
        return match ($mimeType) {
            'image/jpeg' => imagecreatefromjpeg($path),
            'image/png' => imagecreatefrompng($path),
            'image/gif' => imagecreatefromgif($path),
            'image/webp' => imagecreatefromwebp($path),
            default => null,
        };
    }

    protected function saveImage($image, string $path, string $mimeType): bool
    {
        return match ($mimeType) {
            'image/jpeg' => imagejpeg($image, $path, $this->quality),
            'image/png' => imagepng($image, $path, (int) floor($this->quality / 10)),
            'image/gif' => imagegif($image, $path),
            'image/webp' => imagewebp($image, $path, $this->quality),
            default => false,
        };
    }

    public function createThumbnail(string $inputPath, string $outputPath, int $size = 150): bool
    {
        return $this->process($inputPath, $outputPath, [
            'width' => $size,
            'height' => $size,
            'maintain_aspect_ratio' => true,
            'force_resize' => true,
        ]);
    }

    public function createFixedThumbnail(string $inputPath, string $outputPath, int $width, int $height): bool
    {
        return $this->process($inputPath, $outputPath, [
            'width' => $width,
            'height' => $height,
            'maintain_aspect_ratio' => false,
            'force_resize' => true,
        ]);
    }

    public function getDimensions(string $path): ?array
    {
        $info = @getimagesize($path);

        if ($info === false) {
            return null;
        }

        return [
            'width' => $info[0],
            'height' => $info[1],
            'type' => $info[2],
            'mime' => $info['mime'],
        ];
    }

    public function isValidImage(string $path): bool
    {
        if (!file_exists($path)) {
            return false;
        }

        $info = @getimagesize($path);

        if ($info === false) {
            return false;
        }

        return in_array($info['mime'], $this->allowedTypes, true);
    }

    public function convert(string $inputPath, string $outputPath, string $outputType): bool
    {
        if (!in_array($outputType, ['jpeg', 'png', 'gif', 'webp'], true)) {
            return false;
        }

        $inputInfo = @getimagesize($inputPath);
        if ($inputInfo === false) {
            return false;
        }

        $mimeType = image_type_to_mime(image_type_to_index($outputType));

        return $this->process($inputPath, $outputPath, ['force_resize' => true])
            && $this->convertMimeType($outputPath, $mimeType);
    }

    protected function convertMimeType(string $path, string $mimeType): bool
    {
        $image = $this->createImageFromFile($path, $mimeType);
        if ($image === null) {
            return false;
        }

        $success = $this->saveImage($image, $path, $mimeType);
        imagedestroy($image);

        return $success;
    }

    public function addWatermark(string $inputPath, string $outputPath, string $watermarkPath, array $position = ['bottom-right']): bool
    {
        $imageInfo = $this->getDimensions($inputPath);
        if ($imageInfo === null) {
            return false;
        }

        $watermarkInfo = $this->getDimensions($watermarkPath);
        if ($watermarkInfo === null) {
            return false;
        }

        $sourceImage = $this->createImageFromFile($inputPath, $imageInfo['mime']);
        $watermarkImage = $this->createImageFromFile($watermarkPath, $watermarkInfo['mime']);

        if ($sourceImage === null || $watermarkImage === null) {
            return false;
        }

        [$x, $y] = $this->calculateWatermarkPosition(
            $imageInfo['width'],
            $imageInfo['height'],
            $watermarkInfo['width'],
            $watermarkInfo['height'],
            $position
        );

        imagecopy(
            $sourceImage,
            $watermarkImage,
            $x,
            $y,
            0,
            0,
            $watermarkInfo['width'],
            $watermarkInfo['height']
        );

        $success = $this->saveImage($sourceImage, $outputPath, $imageInfo['mime']);

        imagedestroy($sourceImage);
        imagedestroy($watermarkImage);

        return $success;
    }

    protected function calculateWatermarkPosition(
        int $imgW,
        int $imgH,
        int $wmW,
        int $wmH,
        array $position
    ): array {
        $padding = 10;

        $x = match ($position[0] ?? 'right') {
            'left' => $padding,
            'center' => (int) (($imgW - $wmW) / 2),
            'right' => $imgW - $wmW - $padding,
            default => $imgW - $wmW - $padding,
        };

        $y = match ($position[1] ?? 'bottom') {
            'top' => $padding,
            'middle' => (int) (($imgH - $wmH) / 2),
            'bottom' => $imgH - $wmH - $padding,
            default => $imgH - $wmH - $padding,
        };

        return [max(0, $x), max(0, $y)];
    }
}
