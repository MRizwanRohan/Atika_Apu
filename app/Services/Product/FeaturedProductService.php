<?php

namespace App\Services\Product;

use App\Models\FeaturedProductSetting;
use App\Models\Product;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

class FeaturedProductService
{
    private const VERSION_KEY = 'featured_product_settings_version';

    /**
     * Homepage payload, or null when the section is off or its product is not publishable.
     */
    public function forHomepage(): ?array
    {
        $key = 'featured_product.p' . Product::getCacheVersion() . '.s' . Cache::get(self::VERSION_KEY, 1);

        return Cache::remember($key, 600, function () {
            $setting = FeaturedProductSetting::query()->first();
            if (! $setting?->enabled || ! $setting->product_id) {
                return null;
            }

            $product = Product::select([...Product::CARD_COLUMNS, 'short_description', 'description'])
                ->where('status', 'Published')
                ->with(['variations.attributes.value.attribute', 'campaigns'])
                ->withAvg('reviews', 'rating')
                ->withCount('reviews')
                ->find($setting->product_id);

            if (! $product) {
                return null;
            }

            $description = $setting->description
                ?: $this->excerpt($product->short_description ?: $product->description);

            $product->setAppends(['avg_rating', 'reviews_count', 'responsive_image'])
                ->makeHidden(['short_description', 'description']);

            return [
                'product' => $product,
                'badge' => $setting->badge ?: FeaturedProductSetting::DEFAULT_BADGE,
                'description' => $description,
                'highlights' => array_values(array_filter($setting->highlights ?? [], 'filled')),
                'bg_color' => $setting->bg_color ?: FeaturedProductSetting::DEFAULT_BG_COLOR,
            ];
        });
    }

    public function save(array $data): FeaturedProductSetting
    {
        $setting = FeaturedProductSetting::query()->first() ?? new FeaturedProductSetting();
        $setting->fill($data)->save();

        Cache::forever(self::VERSION_KEY, Cache::get(self::VERSION_KEY, 1) + 1);

        return $setting;
    }

    private function excerpt(?string $html): ?string
    {
        if (! $html) {
            return null;
        }

        $text = trim(preg_replace('/\s+/u', ' ', html_entity_decode(strip_tags($html), ENT_QUOTES | ENT_HTML5)));

        return $text === '' ? null : Str::limit($text, 220);
    }
}
