<script setup>
import { Head, Link } from "@inertiajs/vue3";
import FrontendLayout from "@/Layouts/FrontendLayout.vue";
import RecipeBanner from "@/Components/Frontend/Recipe/RecipeBanner.vue";
import RecipeCard from "@/Components/Frontend/Recipe/RecipeCard.vue";

defineOptions({ layout: FrontendLayout });

defineProps({
    recipes: { type: Object, required: true },
    section: { type: Object, default: null },
});
</script>

<template>
    <Head title="Recipes" />

    <RecipeBanner
        v-if="section?.banner"
        :banner="section.banner"
        :responsive-banner="section.responsive_banner"
        :link="section.banner_link || ''"
        :alt="section.title"
    />

    <section class="container mx-auto px-4 py-8 md:py-14">
        <nav class="mb-4 text-xs md:text-sm text-gray-500" aria-label="Breadcrumb">
            <Link :href="route('home')" class="hover:text-primary">Home</Link>
            <span class="mx-2">/</span>
            <span class="text-gray-900">Recipes</span>
        </nav>

        <p class="text-xs md:text-sm font-semibold uppercase tracking-[0.15em] text-primary">
            {{ section?.label || "Recipe Inspiration" }}
        </p>
        <h1 class="mt-1 mb-6 md:mb-10 font-display font-bold uppercase leading-tight tracking-wide text-primary text-3xl md:text-5xl">
            All Recipes
        </h1>

        <div v-if="recipes.data.length" class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-x-3 gap-y-6 md:gap-x-6 md:gap-y-10">
            <RecipeCard
                v-for="recipe in recipes.data"
                :key="recipe.id"
                :recipe="recipe"
                sizes="(min-width: 1024px) 300px, (min-width: 768px) 33vw, 50vw"
            />
        </div>
        <p v-else class="py-16 text-center text-gray-500">No recipes yet. Check back soon!</p>

        <nav v-if="recipes.last_page > 1" class="mt-10 flex flex-wrap justify-center gap-2" aria-label="Pagination">
            <template v-for="(link, i) in recipes.links" :key="i">
                <Link
                    v-if="link.url"
                    :href="link.url"
                    class="min-w-10 h-10 px-3 flex items-center justify-center rounded-md border text-sm transition-colors"
                    :class="link.active ? 'bg-primary border-primary text-white' : 'border-gray-200 text-gray-700 hover:border-primary hover:text-primary'"
                >
                    <span v-html="link.label" />
                </Link>
                <span
                    v-else
                    class="min-w-10 h-10 px-3 flex items-center justify-center rounded-md border border-gray-100 text-sm text-gray-300"
                    v-html="link.label"
                />
            </template>
        </nav>
    </section>
</template>
