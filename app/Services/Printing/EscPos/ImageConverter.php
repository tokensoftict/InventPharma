<?php

namespace App\Services\Printing\EscPos;

/**
 * Converts images (PNG, JPG, GIF) to ESC/POS raster bitmap format.
 * Uses PHP GD library for image processing.
 */
class ImageConverter
{
    /**
     * Convert an image file to ESC/POS raster bitmap data.
     *
     * @param string $imagePath Absolute path to the image file
     * @param int $maxWidth Maximum width in dots (576 for 80mm, 384 for 58mm)
     * @param int $threshold Brightness threshold for monochrome (0-255)
     * @return array{width: int, height: int, data: string}
     * @throws \RuntimeException
     */
    public static function convert(string $imagePath, int $maxWidth = 576, int $threshold = 127): array
    {
        if (!file_exists($imagePath)) {
            throw new \RuntimeException("Image file not found: {$imagePath}");
        }

        if (!extension_loaded('gd')) {
            throw new \RuntimeException("GD extension is required for image conversion");
        }

        $imageInfo = getimagesize($imagePath);
        if ($imageInfo === false) {
            throw new \RuntimeException("Cannot read image: {$imagePath}");
        }

        $image = self::loadImage($imagePath, $imageInfo[2]);
        if ($image === false) {
            throw new \RuntimeException("Failed to load image: {$imagePath}");
        }

        $origWidth = imagesx($image);
        $origHeight = imagesy($image);

        // Scale down if wider than max width
        if ($origWidth > $maxWidth) {
            $newWidth = $maxWidth;
            $newHeight = (int) round($origHeight * ($maxWidth / $origWidth));
            $scaled = imagecreatetruecolor($newWidth, $newHeight);
            imagealphablending($scaled, false);
            imagesavealpha($scaled, true);
            imagecopyresampled($scaled, $image, 0, 0, 0, 0, $newWidth, $newHeight, $origWidth, $origHeight);
            imagedestroy($image);
            $image = $scaled;
        }

        $width = imagesx($image);
        $height = imagesy($image);

        // Width must be a multiple of 8 for byte alignment
        $alignedWidth = (int) ceil($width / 8) * 8;

        // Convert to monochrome using Floyd-Steinberg dithering
        $pixels = self::dither($image, $alignedWidth, $height, $threshold);
        imagedestroy($image);

        // Pack into bytes (1 bit per pixel, MSB first)
        $bytesPerLine = $alignedWidth / 8;
        $data = '';

        for ($y = 0; $y < $height; $y++) {
            for ($byteIdx = 0; $byteIdx < $bytesPerLine; $byteIdx++) {
                $byte = 0;
                for ($bit = 0; $bit < 8; $bit++) {
                    $x = $byteIdx * 8 + $bit;
                    if ($x < $width && $pixels[$y][$x]) {
                        $byte |= (0x80 >> $bit); // MSB first, 1 = black dot
                    }
                }
                $data .= chr($byte);
            }
        }

        return [
            'width' => $alignedWidth,
            'height' => $height,
            'data' => $data,
        ];
    }

    /**
     * Load image based on type.
     */
    private static function loadImage(string $path, int $type)
    {
        switch ($type) {
            case IMAGETYPE_JPEG:
                return imagecreatefromjpeg($path);
            case IMAGETYPE_PNG:
                return imagecreatefrompng($path);
            case IMAGETYPE_GIF:
                return imagecreatefromgif($path);
            case IMAGETYPE_BMP:
                return imagecreatefrombmp($path);
            case IMAGETYPE_WEBP:
                return imagecreatefromwebp($path);
            default:
                return false;
        }
    }

    /**
     * Floyd-Steinberg dithering for better quality monochrome conversion.
     * Returns a 2D array of booleans (true = black/print, false = white/no print).
     */
    private static function dither($image, int $width, int $height, int $threshold): array
    {
        $imgWidth = imagesx($image);

        // Create grayscale array
        $gray = [];
        for ($y = 0; $y < $height; $y++) {
            $gray[$y] = [];
            for ($x = 0; $x < $width; $x++) {
                if ($x < $imgWidth) {
                    $rgb = imagecolorat($image, $x, $y);

                    // Handle transparency (treat as white)
                    $alpha = ($rgb >> 24) & 0x7F;
                    if ($alpha > 64) {
                        $gray[$y][$x] = 255.0; // White for transparent
                        continue;
                    }

                    $r = ($rgb >> 16) & 0xFF;
                    $g = ($rgb >> 8) & 0xFF;
                    $b = $rgb & 0xFF;

                    // Luminance conversion
                    $gray[$y][$x] = 0.299 * $r + 0.587 * $g + 0.114 * $b;
                } else {
                    $gray[$y][$x] = 255.0; // White padding for alignment
                }
            }
        }

        // Floyd-Steinberg dithering
        $result = [];
        for ($y = 0; $y < $height; $y++) {
            $result[$y] = [];
            for ($x = 0; $x < $width; $x++) {
                $oldPixel = $gray[$y][$x];
                $newPixel = $oldPixel < $threshold ? 0 : 255;
                $result[$y][$x] = ($newPixel === 0); // true = black (print)
                $error = $oldPixel - $newPixel;

                // Distribute error to neighbors
                if ($x + 1 < $width) {
                    $gray[$y][$x + 1] += $error * 7 / 16;
                }
                if ($y + 1 < $height) {
                    if ($x - 1 >= 0) {
                        $gray[$y + 1][$x - 1] += $error * 3 / 16;
                    }
                    $gray[$y + 1][$x] += $error * 5 / 16;
                    if ($x + 1 < $width) {
                        $gray[$y + 1][$x + 1] += $error * 1 / 16;
                    }
                }
            }
        }

        return $result;
    }
}
