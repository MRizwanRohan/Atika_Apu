<?php

namespace App\Console\Commands;

use App\Support\ResponsiveImages;
use Illuminate\Console\Command;
use Symfony\Component\Finder\Finder;

class OptimizeResponsiveImages extends Command
{
    protected $signature = 'images:optimize {--force : Regenerate existing responsive variants}';
    protected $description = 'Create responsive WebP sizes beside existing storefront images, preserving originals';

    public function handle(): int
    {
        $count = 0;
        foreach (['storage', 'uploads', 'assets/img'] as $directory) {
            $path = public_path($directory);
            if (!is_dir($path)) continue;
            $files = Finder::create()->files()->in($path)->name('/\.(jpe?g|png|webp)$/i')->notName('/\.w\d+\.webp$/');
            foreach ($files as $file) {
                $source = $file->getPathname();
                if (!$this->option('force') && is_file($source . '.responsive.json')) continue;
                try {
                    if (ResponsiveImages::generate($source)) $count++;
                } catch (\Throwable $exception) {
                    $this->error($exception->getMessage());
                    return self::FAILURE;
                }
            }
        }
        $this->info("Responsive image metadata generated for {$count} images; originals preserved.");
        return self::SUCCESS;
    }
}
