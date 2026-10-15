<?php

namespace App\Helpers;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\File;

class ImageHelper
{
    public static function uploadImage(UploadedFile $file, $path)
    {
        // Ensure directory exists
        $fullPath = public_path($path);
        if (!File::exists($fullPath)) {
            File::makeDirectory($fullPath, 0755, true);
        }

        // Generate filename (always webp)
        $filename = time() . '_' . uniqid() . '.webp';
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

        // ✅ Convert & compress to WebP (quality: 70)
        imagewebp($image, $destination, 70);
        imagedestroy($image);
        try {
            \App\Support\ResponsiveImages::generate($destination);
        } catch (\Throwable $exception) {
            // The original upload remains usable if optional variants cannot be written.
            report($exception);
        }

        // Return full URL
        return url($path . '/' . $filename);
    }

    public static function deleteImage($url)
    {
        // Remove domain
        $relativePath = str_replace(url('/'), '', $url);
        $fullPath = public_path($relativePath);

        if (File::exists($fullPath)) {
            File::delete(glob(dirname($fullPath) . '/' . pathinfo($fullPath, PATHINFO_FILENAME) . '.w*.webp') ?: []);
            File::delete($fullPath . '.responsive.json');
            File::delete($fullPath);
        }
    }
}
