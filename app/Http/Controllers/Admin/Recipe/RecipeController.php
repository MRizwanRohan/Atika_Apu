<?php

namespace App\Http\Controllers\Admin\Recipe;

use App\Helpers\ImageHelper;
use App\Http\Controllers\Controller;
use App\Models\Recipe;
use App\Models\RecipeSectionSetting;
use App\Services\Recipe\RecipeService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class RecipeController extends Controller
{
    // ImageHelper converts only these formats to WebP.
    private const IMAGE_RULE = 'image|mimes:jpeg,jpg,png,webp|max:6000';

    public function __construct(protected RecipeService $service) {}

    public function index(Request $request): Response
    {
        $search = trim((string) $request->query('search', ''));

        $recipes = Recipe::query()
            ->when($search !== '', fn ($q) => $q->where(fn ($q) => $q
                ->where('title', 'like', "%{$search}%")
                ->orWhere('subtitle', 'like', "%{$search}%")))
            ->ordered()
            ->paginate(15, ['id', 'title', 'subtitle', 'slug', 'image', 'status', 'sort_order'])
            ->withQueryString();

        $setting = $this->service->sectionSetting();

        return Inertia::render('Admin/Recipes/Index', [
            'recipes' => $recipes,
            'filters' => ['search' => $search],
            'section' => [
                'enabled' => $setting->enabled,
                'label' => $setting->label ?? RecipeSectionSetting::DEFAULT_LABEL,
                'title' => $setting->title ?? RecipeSectionSetting::DEFAULT_TITLE,
                'banner' => $setting->banner,
                'banner_link' => $setting->banner_link ?? '',
            ],
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('Admin/Recipes/Form', ['recipe' => null]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validateRecipe($request, true);

        $data['image'] = ImageHelper::uploadImage($request->file('image'), 'storage/recipes');
        $data['slug'] = Recipe::uniqueSlug($data['title']);

        Recipe::create($data);
        $this->service->flush();

        return redirect()->route('admin.recipes.index')->with('success', 'Recipe created.');
    }

    public function edit(Recipe $recipe): Response
    {
        return Inertia::render('Admin/Recipes/Form', ['recipe' => $recipe]);
    }

    public function update(Request $request, Recipe $recipe): RedirectResponse
    {
        $data = $this->validateRecipe($request, false);

        if ($request->hasFile('image')) {
            $old = $recipe->image;
            $data['image'] = ImageHelper::uploadImage($request->file('image'), 'storage/recipes');
            ImageHelper::deleteImage($old);
        } else {
            unset($data['image']);
        }

        if ($recipe->title !== $data['title']) {
            $data['slug'] = Recipe::uniqueSlug($data['title'], $recipe->id);
        }

        $recipe->update($data);
        $this->service->flush();

        return redirect()->route('admin.recipes.index')->with('success', 'Recipe updated.');
    }

    public function toggleStatus(Recipe $recipe): RedirectResponse
    {
        $recipe->update(['status' => ! $recipe->status]);
        $this->service->flush();

        return back()->with('success', $recipe->status ? 'Recipe is now visible.' : 'Recipe hidden.');
    }

    public function destroy(Recipe $recipe): RedirectResponse
    {
        $image = $recipe->image;
        $recipe->delete();
        ImageHelper::deleteImage($image);
        $this->service->flush();

        return back()->with('success', 'Recipe deleted.');
    }

    public function updateSection(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'enabled' => ['required', 'boolean'],
            'label' => ['nullable', 'string', 'max:60'],
            'title' => ['nullable', 'string', 'max:100'],
            'banner_file' => ['nullable', 'image', 'mimes:jpeg,jpg,png,webp', 'max:8000'],
            'remove_banner' => ['nullable', 'boolean'],
            'banner_link' => ['nullable', 'string', 'max:255', 'regex:#^(https?://|/)#i'],
        ], [
            'banner_link.regex' => 'The link must start with http://, https:// or /.',
        ]);

        $setting = RecipeSectionSetting::query()->first() ?? new RecipeSectionSetting();
        $oldBanner = $setting->banner;

        if ($request->hasFile('banner_file')) {
            $setting->banner = ImageHelper::uploadImage($request->file('banner_file'), 'storage/recipes/banner');
        } elseif ($request->boolean('remove_banner')) {
            $setting->banner = null;
        }

        $setting->fill([
            'enabled' => $data['enabled'],
            'label' => $data['label'] ?? null,
            'title' => $data['title'] ?? null,
            'banner_link' => $data['banner_link'] ?? null,
        ])->save();

        if ($oldBanner && $oldBanner !== $setting->banner) {
            ImageHelper::deleteImage($oldBanner);
        }

        $this->service->flush();

        return back()->with('success', 'Recipe section updated.');
    }

    private function validateRecipe(Request $request, bool $creating): array
    {
        $data = $request->validate([
            'title' => ['required', 'string', 'max:120'],
            'subtitle' => ['nullable', 'string', 'max:120'],
            'image' => [$creating ? 'required' : 'nullable', ...explode('|', self::IMAGE_RULE)],
            'content' => ['nullable', 'string', 'max:65000'],
            'status' => ['required', 'boolean'],
            'sort_order' => ['nullable', 'integer', 'min:0', 'max:9999'],
        ]);
        $data['sort_order'] = (int) ($data['sort_order'] ?? 0);

        return $data;
    }
}
