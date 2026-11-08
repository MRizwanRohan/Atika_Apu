<script setup>
import { computed } from "vue";
import { Head, Link } from "@inertiajs/vue3";
import DOMPurify from "dompurify";
import FrontendLayout from "@/Layouts/FrontendLayout.vue";
import RecipeCard from "@/Components/Frontend/Recipe/RecipeCard.vue";

defineOptions({ layout: FrontendLayout });

const props = defineProps({
    recipe: { type: Object, required: true },
    moreRecipes: { type: Array, default: () => [] },
});

const content = computed(() => (props.recipe.content ? DOMPurify.sanitize(props.recipe.content) : ""));
const description = computed(() =>
    [props.recipe.title, props.recipe.subtitle].filter(Boolean).join(" ").slice(0, 160),
);
</script>

<template>
    <Head :title="recipe.title">
        <meta name="description" :content="description" />
    </Head>

    <article class="container mx-auto px-4 py-6 md:py-12">
        <nav class="mb-4 md:mb-6 text-xs md:text-sm text-gray-500" aria-label="Breadcrumb">
            <Link :href="route('home')" class="hover:text-primary">Home</Link>
            <span class="mx-2">/</span>
            <Link :href="route('recipes.index')" class="hover:text-primary">Recipes</Link>
            <span class="mx-2">/</span>
            <span class="text-gray-900">{{ recipe.title }}</span>
        </nav>

        <div class="grid md:grid-cols-2 gap-6 md:gap-12 items-start">
            <div class="aspect-[4/3] overflow-hidden rounded-2xl bg-gray-100 md:sticky md:top-40">
                <img
                    :src="recipe.image"
                    :srcset="recipe.responsive_image?.srcset"
                    sizes="(min-width: 768px) 50vw, 100vw"
                    :width="recipe.responsive_image?.width"
                    :height="recipe.responsive_image?.height"
                    :alt="recipe.title"
                    fetchpriority="high"
                    class="w-full h-full object-cover"
                />
            </div>

            <div class="min-w-0">
                <p class="text-xs md:text-sm font-semibold uppercase tracking-[0.15em] text-primary">Recipe</p>
                <h1 class="mt-1 font-display font-bold uppercase leading-tight tracking-wide text-primary text-3xl md:text-5xl break-words">
                    {{ recipe.title }}
                </h1>
                <p v-if="recipe.subtitle" class="mt-2 text-base md:text-lg text-gray-600">{{ recipe.subtitle }}</p>

                <div v-if="content" class="recipe-content mt-6 md:mt-8" v-html="content" />
            </div>
        </div>
    </article>

    <section v-if="moreRecipes.length" class="container mx-auto px-4 pb-12 md:pb-16">
        <div class="flex items-end justify-between gap-4 mb-5 md:mb-8">
            <h2 class="font-display font-bold uppercase tracking-wide text-primary text-2xl md:text-3xl">More Recipes</h2>
            <Link
                :href="route('recipes.index')"
                class="hidden text-sm md:flex items-center gap-2 text-primary font-semibold hover:gap-3 transition-all duration-300"
            >
                View All Recipes
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M13 7l5 5m0 0l-5 5m5-5H6" />
                </svg>
            </Link>
        </div>
        <div class="grid grid-cols-2 lg:grid-cols-4 gap-x-3 gap-y-6 md:gap-6">
            <RecipeCard v-for="item in moreRecipes" :key="item.id" :recipe="item" />
        </div>
    </section>
</template>

<style scoped>
.recipe-content {
    @apply text-gray-700 leading-relaxed text-sm md:text-base;
}
.recipe-content :deep(h1) {
    @apply mt-8 mb-3 text-2xl md:text-3xl font-bold text-gray-900;
}
.recipe-content :deep(li > h1),
.recipe-content :deep(li > h2),
.recipe-content :deep(li > h3) {
    @apply my-1;
}
.recipe-content :deep(h2) {
    @apply mt-8 mb-3 text-xl md:text-2xl font-bold text-gray-900;
}
.recipe-content :deep(h3) {
    @apply mt-6 mb-2 text-lg md:text-xl font-semibold text-gray-900;
}
.recipe-content :deep(h4) {
    @apply mt-5 mb-2 font-semibold text-gray-900;
}
.recipe-content :deep(p) {
    @apply my-3;
}
.recipe-content :deep(ul) {
    @apply my-3 pl-5 list-disc space-y-1;
}
.recipe-content :deep(ol) {
    @apply my-3 pl-5 list-decimal space-y-1;
}
.recipe-content :deep(a) {
    @apply text-primary underline underline-offset-2;
}
.recipe-content :deep(strong) {
    @apply font-semibold text-gray-900;
}
.recipe-content :deep(blockquote) {
    @apply my-4 pl-4 border-l-4 border-gray-200 italic text-gray-600;
}
.recipe-content :deep(img) {
    @apply my-4 max-w-full h-auto rounded-lg;
}
.recipe-content :deep(table) {
    @apply my-4 w-full text-sm border-collapse;
}
.recipe-content :deep(th),
.recipe-content :deep(td) {
    @apply border border-gray-200 px-3 py-2 text-left;
}
</style>
