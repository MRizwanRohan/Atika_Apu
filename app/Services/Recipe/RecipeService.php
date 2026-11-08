<?php

namespace App\Services\Recipe;

use App\Models\Recipe;
use App\Models\RecipeSectionSetting;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;

class RecipeService
{
    private const VERSION_KEY = 'recipes_cache_version';
    private const CARD_COLUMNS = ['id', 'title', 'subtitle', 'slug', 'image'];
    public const HOMEPAGE_LIMIT = 5;

    /**
     * Homepage payload, or null when the section is switched off or has nothing to show.
     */
    public function forHomepage(): ?array
    {
        return Cache::remember($this->key('homepage'), 1800, function () {
            $setting = RecipeSectionSetting::current();
            if (! $setting->enabled) {
                return null;
            }

            $recipes = $this->cards(Recipe::active()->ordered()->limit(self::HOMEPAGE_LIMIT));
            if ($recipes->isEmpty() && ! $setting->banner) {
                return null;
            }

            return [
                'label' => $setting->label ?: RecipeSectionSetting::DEFAULT_LABEL,
                'title' => $setting->title ?: RecipeSectionSetting::DEFAULT_TITLE,
                'banner' => $setting->banner,
                'responsive_banner' => $setting->banner ? $setting->responsive_banner : [],
                'banner_link' => $setting->banner_link,
                'recipes' => $recipes,
                'has_more' => Recipe::active()->count() > self::HOMEPAGE_LIMIT,
            ];
        });
    }

    public function paginated(int $page = 1): LengthAwarePaginator
    {
        return Cache::remember($this->key("index.page{$page}"), 1800, function () use ($page) {
            return Recipe::active()->ordered()
                ->paginate(12, self::CARD_COLUMNS, 'page', $page)
                ->through(fn (Recipe $recipe) => $recipe->setAppends(['responsive_image']));
        });
    }

    public function findBySlug(string $slug): ?Recipe
    {
        return Cache::remember($this->key("show.{$slug}"), 1800, function () use ($slug) {
            return Recipe::active()->where('slug', $slug)->first()?->setAppends(['responsive_image']);
        });
    }

    public function moreLike(Recipe $recipe, int $limit = 4): Collection
    {
        return Cache::remember($this->key("more.{$recipe->id}"), 1800, fn () =>
            $this->cards(Recipe::active()->ordered()->whereKeyNot($recipe->id)->limit($limit))
        );
    }

    public function sectionSetting(): RecipeSectionSetting
    {
        return RecipeSectionSetting::current();
    }

    public function flush(): void
    {
        Cache::forever(self::VERSION_KEY, Cache::get(self::VERSION_KEY, 1) + 1);
    }

    private function cards($query): Collection
    {
        return $query->get(self::CARD_COLUMNS)->each->setAppends(['responsive_image']);
    }

    private function key(string $suffix): string
    {
        return 'recipes.v' . Cache::get(self::VERSION_KEY, 1) . '.' . $suffix;
    }
}
