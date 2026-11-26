<?php

namespace App\Http\Controllers\Frontend\Blog;

use App\Http\Controllers\Controller;
use App\Models\Blog;
use Inertia\Inertia;
use Inertia\Response;

/**
 * "Wellness" on the storefront: the published blog posts managed under Admin > Blogs.
 */
class WellnessController extends Controller
{
    private const CARD_FIELDS = ['id', 'title', 'slug', 'excerpt', 'featured_image', 'author_name', 'published_at'];

    public function index(): Response
    {
        return Inertia::render('Frontend/Wellness/Index', [
            'posts' => Blog::published()->latest()->paginate(9, self::CARD_FIELDS)->withQueryString(),
        ]);
    }

    public function show(string $slug): Response
    {
        $post = Blog::published()->where('slug', $slug)->firstOrFail();
        $post->incrementViews();

        return Inertia::render('Frontend/Wellness/Show', [
            'post' => $post->only([
                'id', 'title', 'slug', 'excerpt', 'content', 'featured_image', 'author_name',
                'published_at', 'meta_title', 'meta_description', 'tags',
            ]),
            'morePosts' => Blog::published()->latest()->where('id', '!=', $post->id)->take(3)->get(self::CARD_FIELDS),
        ]);
    }
}
