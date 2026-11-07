<?php

namespace App\Services;

use Illuminate\Support\Facades\Storage;
use RuntimeException;

class AvatarImageOptimizer
{
    public function optimize(string $sourcePath, string $storagePath): string
    {
        $imageInfo = getimagesize($sourcePath);
        if ($imageInfo === false) {
            throw new RuntimeException('The uploaded avatar could not be read.');
        }

        [$width, $height, $type] = $imageInfo;
        if (! in_array($type, [IMAGETYPE_JPEG, IMAGETYPE_PNG, IMAGETYPE_WEBP], true)) {
            throw new RuntimeException('The uploaded avatar format is not supported.');
        }

        if ($width > 8192 || $height > 8192 || $width * $height > 16_777_216) {
            throw new RuntimeException('The uploaded avatar dimensions are too large to optimize.');
        }

        $sourceBytes = file_get_contents($sourcePath);
        if ($sourceBytes === false) {
            throw new RuntimeException('The uploaded avatar could not be read.');
        }

        $source = imagecreatefromstring($sourceBytes);
        if ($source === false) {
            throw new RuntimeException('The uploaded avatar could not be decoded.');
        }

        $cropSize = min($width, $height);
        $offsetX = (int) (($width - $cropSize) / 2);
        $offsetY = (int) (($height - $cropSize) / 2);
        $variantSizes = $cropSize >= 800 ? [480, 800] : [$cropSize];
        $pathInfo = pathinfo($storagePath);
        $basePath = ($pathInfo['dirname'] !== '.' ? $pathInfo['dirname'] . '/' : '')
            . $pathInfo['filename'];
        $createdPaths = [];

        try {
            foreach ($variantSizes as $size) {
                $optimizedPath = $basePath . ($size === 480 ? '.optimized-480.webp' : '.optimized.webp');
                $this->writeVariant($source, $offsetX, $offsetY, $cropSize, $size, $optimizedPath);
                $createdPaths[] = $optimizedPath;
            }

            return $basePath . '.optimized.webp';
        } catch (RuntimeException $exception) {
            Storage::disk('public')->delete($createdPaths);
            throw $exception;
        } finally {
            imagedestroy($source);
        }
    }

    private function writeVariant(
        \GdImage $source,
        int $offsetX,
        int $offsetY,
        int $cropSize,
        int $targetSize,
        string $optimizedPath,
    ): void {
        $optimized = imagecreatetruecolor($targetSize, $targetSize);
        if ($optimized === false) {
            throw new RuntimeException('The optimized avatar could not be created.');
        }

        imagealphablending($optimized, false);
        imagesavealpha($optimized, true);
        $transparent = imagecolorallocatealpha($optimized, 0, 0, 0, 127);
        imagefilledrectangle($optimized, 0, 0, $targetSize, $targetSize, $transparent);

        try {
            if (! imagecopyresampled(
                $optimized,
                $source,
                0,
                0,
                $offsetX,
                $offsetY,
                $targetSize,
                $targetSize,
                $cropSize,
                $cropSize,
            )) {
                throw new RuntimeException('The uploaded avatar could not be resized.');
            }

            ob_start();
            $encoded = imagewebp($optimized, null, 82);
            $webpBytes = ob_get_clean();

            if (! $encoded || ! is_string($webpBytes) || $webpBytes === '') {
                throw new RuntimeException('The optimized avatar could not be encoded as WebP.');
            }

            if (! Storage::disk('public')->put($optimizedPath, $webpBytes)) {
                Storage::disk('public')->delete($optimizedPath);
                throw new RuntimeException('The optimized avatar could not be saved.');
            }
        } finally {
            imagedestroy($optimized);
        }
    }
}
