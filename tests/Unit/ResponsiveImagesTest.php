<?php

namespace Tests\Unit;

use App\Support\ResponsiveImages;
use Tests\TestCase;

class ResponsiveImagesTest extends TestCase
{
    public function test_variants_preserve_original_dimensions_transparency_and_small_image_fallback(): void
    {
        $directory = public_path('uploads/performance-test-' . uniqid());
        mkdir($directory, 0755, true);
        try {
            $source = $directory . '/sample.png';
            $image = imagecreatetruecolor(800, 400);
            imagealphablending($image, false);
            imagesavealpha($image, true);
            imagefill($image, 0, 0, imagecolorallocatealpha($image, 0, 0, 0, 127));
            imagepng($image, $source);
            imagedestroy($image);
            $original = hash_file('sha256', $source);
            $this->assertTrue(ResponsiveImages::generate($source));
            $this->assertSame($original, hash_file('sha256', $source));
            $this->assertSame([320, 160], array_slice(getimagesize($directory . '/sample.w320.webp'), 0, 2));
            $variant = imagecreatefromwebp($directory . '/sample.w320.webp');
            $this->assertSame(127, (imagecolorat($variant, 0, 0) >> 24) & 127);
            imagedestroy($variant);
            $url = url('uploads/' . basename($directory) . '/sample.png');
            $attributes = (new ResponsiveImages)->attributes($url);
            $this->assertSame(800, $attributes['width']);
            $this->assertStringContainsString('sample.w320.webp 320w', $attributes['srcset']);
            $this->assertStringContainsString('sample.png 800w', $attributes['srcset']);
            $this->assertSame([], (new ResponsiveImages)->attributes('https://external.example/image.png'));
            $this->assertFileDoesNotExist($directory . '/sample.w960.webp');
        } finally {
            foreach (glob($directory . '/*') as $file) unlink($file);
            rmdir($directory);
        }
    }
}
