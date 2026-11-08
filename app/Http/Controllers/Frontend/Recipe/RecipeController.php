<?php

namespace App\Http\Controllers\Frontend\Recipe;

use App\Http\Controllers\Controller;
use App\Services\Recipe\RecipeService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class RecipeController extends Controller
{
    public function __construct(protected RecipeService $service) {}

    public function index(Request $request): Response
    {
        $page = max(1, min(10000, (int) $request->query('page', 1)));

        return Inertia::render('Frontend/Recipes/Index', [
            'recipes' => $this->service->paginated($page)->withPath(route('recipes.index')),
            'section' => $this->service->forHomepage(),
        ]);
    }

    public function show(string $slug): Response
    {
        $recipe = $this->service->findBySlug($slug);
        abort_unless($recipe, 404);

        return Inertia::render('Frontend/Recipes/Show', [
            'recipe' => $recipe,
            'moreRecipes' => $this->service->moreLike($recipe),
        ]);
    }
}
