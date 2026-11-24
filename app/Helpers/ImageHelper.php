<?php

namespace App\Helpers;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\File;

class ImageHelper
{
    /** Output formats in order of preference, each with its quality. The first one this server can encode is used. */
    public const DEFAULT_ENCODING = ['webp' => 70];
    public const HIGH_QUALITY = ['avif' => 92, 'webp' => 95];

    public static function uploadImage(UploadedFile $file, $path, array $encoding = self::DEFAULT_ENCODING)
    {
        [$format, $quality] = self::pickEncoding($encoding);

        // Ensure directory exists
        $fullPath = public_path($path);
        if (!File::exists($fullPath)) {
            File::makeDirectory($fullPath, 0755, true);
        }

        $filename = time() . '_' . uniqid() . '.' . $format;
        $destination = $fullPath . '/' . $filename;

        // Create image resource based on type
        $extension = strtolower($file->getClientOriginalExtension());

        switch ($extension) {
            case 'jpg':
            case 'jpeg':
                $image = imagecreatefromjpeg($file->getRealPath());
                break;

            case 'png':
                $image = imagecreatefrompng($file->getRealPath());
                imagepalettetotruecolor($image);
                imagealphablending($image, true);
                imagesavealpha($image, true);
                break;

            case 'webp':
                $image = imagecreatefromwebp($file->getRealPath());
                break;

            default:
                throw new \Exception('Unsupported image type');
        }

        $written = $format === 'avif'
            ? imageavif($image, $destination, $quality)
            : imagewebp($image, $destination, $quality);
        imagedestroy($image);
        if (!$written) {
            throw new \RuntimeException("Unable to write {$format} image: {$destination}");
        }
        try {
            \App\Support\ResponsiveImages::generate($destination, $format, $quality);
        } catch (\Throwable $exception) {
            // The original upload remains usable if optional variants cannot be written.
            report($exception);
        }

        // Return full URL
        return url($path . '/' . $filename);
    }

    /** @return array{0: string, 1: int} [format, quality] */
    private static function pickEncoding(array $encoding): array
    {
        foreach ($encoding as $format => $quality) {
            if (in_array($format, ['avif', 'webp'], true) && function_exists('image' . $format)) {
                return [$format, (int) $quality];
            }
        }

        return ['webp', self::DEFAULT_ENCODING['webp']];
    }

    public static function deleteImage($url)
    {
        // Remove domain
        $relativePath = str_replace(url('/'), '', $url);
        $fullPath = public_path($relativePath);

        if (File::exists($fullPath)) {
            $stem = dirname($fullPath) . '/' . pathinfo($fullPath, PATHINFO_FILENAME);
            File::delete(array_merge(glob($stem . '.w*.webp') ?: [], glob($stem . '.w*.avif') ?: []));
            File::delete($fullPath . '.responsive.json');
            File::delete($fullPath);
        }
    }
}
