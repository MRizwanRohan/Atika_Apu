<?php

namespace App\Support;

class ResponsiveImages
{
    private array $attributes = [];

    public function attributes(?string $url): array
    {
        if (!$url) return [];
        if (array_key_exists($url, $this->attributes)) return $this->attributes[$url];
        $path = $this->localPath($url);
        $metadata = $path && is_file($path . '.responsive.json')
            ? json_decode(file_get_contents($path . '.responsive.json'), true) : null;
        if (!$metadata) return $this->attributes[$url] = [];

        $base = preg_replace('/[?#].*$/', '', $url);
        $sources = [];
        foreach ($metadata['variants'] as $width => $filename) {
            $sources[] = substr($base, 0, strrpos($base, '/') + 1) . $filename . " {$width}w";
        }
        $sources[] = $base . " {$metadata['width']}w";
        return $this->attributes[$url] = [
            'srcset' => implode(', ', $sources),
            'width' => $metadata['width'],
            'height' => $metadata['height'],
        ];
    }

    private function localPath(string $url): ?string
    {
        $host = parse_url($url, PHP_URL_HOST);
        if ($host && !in_array($host, [parse_url(config('app.url'), PHP_URL_HOST), request()->getHost()], true)) return null;
        $relative = rawurldecode(parse_url($url, PHP_URL_PATH) ?? '');
        $path = realpath(public_path(ltrim($relative, '/')));
        if (!$path || !is_file($path)) return null;
        foreach ([realpath(public_path()), realpath(storage_path('app/public'))] as $root) {
            if ($root && str_starts_with($path, $root . DIRECTORY_SEPARATOR)) return $path;
        }
        return null;
    }

    public static function generate(string $path): bool
    {
        $size = @getimagesize($path);
        // Preserve animated GIFs and originals; cap decoding memory for large uploads.
        if (!$size || !in_array($size[2], [IMAGETYPE_JPEG, IMAGETYPE_PNG, IMAGETYPE_WEBP], true)
            || $size[0] * $size[1] > 20000000 || !function_exists('imagewebp')) return false;
        $source = @imagecreatefromstring(file_get_contents($path));
        if (!$source) return false;
        $variants = [];
        try {
            foreach ([320, 640, 960, 1280] as $width) {
                if ($width >= $size[0]) continue;
                $height = max(1, (int) round($size[1] * $width / $size[0]));
                $image = imagecreatetruecolor($width, $height);
                imagealphablending($image, false);
                imagesavealpha($image, true);
                imagecopyresampled($image, $source, 0, 0, 0, 0, $width, $height, $size[0], $size[1]);
                $filename = pathinfo($path, PATHINFO_FILENAME) . ".w{$width}.webp";
                $destination = dirname($path) . '/' . $filename;
                $temporary = $destination . '.' . uniqid() . '.tmp';
                try {
                    if (!imagewebp($image, $temporary, 70) || !rename($temporary, $destination)) {
                        throw new \RuntimeException("Unable to write responsive image: {$destination}");
                    }
                } finally {
                    imagedestroy($image);
                    if (is_file($temporary)) unlink($temporary);
                }
                $variants[$width] = $filename;
            }
            $metadata = json_encode(['width' => $size[0], 'height' => $size[1], 'variants' => $variants], JSON_THROW_ON_ERROR);
            $temporary = $path . '.responsive.' . uniqid() . '.tmp';
            try {
                if (file_put_contents($temporary, $metadata) === false || !rename($temporary, $path . '.responsive.json')) {
                    throw new \RuntimeException('Unable to write responsive image metadata.');
                }
            } finally {
                if (is_file($temporary)) unlink($temporary);
            }
        } finally {
            imagedestroy($source);
        }
        return true;
    }
}
