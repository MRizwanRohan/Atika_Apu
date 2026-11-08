<script setup>
import { Link } from "@inertiajs/vue3";
import RecipeBanner from "@/Components/Frontend/Recipe/RecipeBanner.vue";
import RecipeCard from "@/Components/Frontend/Recipe/RecipeCard.vue";

defineProps({
    section: { type: Object, required: true },
});
</script>

<template>
    <section class="bg-white">
        <RecipeBanner
            v-if="section.banner"
            :banner="section.banner"
            :responsive-banner="section.responsive_banner"
            :link="section.banner_link || ''"
            :alt="section.title"
        />

        <div v-if="section.recipes.length" class="container mx-auto px-4 py-8 md:py-14">
            <div class="flex items-end justify-between gap-4 mb-5 md:mb-8">
                <div class="min-w-0">
                    <p class="text-xs md:text-sm font-semibold uppercase tracking-[0.15em] text-primary">
                        {{ section.label }}
                    </p>
                    <h2 class="mt-1 font-display font-bold uppercase leading-tight tracking-wide text-primary text-2xl sm:text-3xl md:text-4xl break-words">
                        {{ section.title }}
                    </h2>
                </div>

                <Link
                    :href="route('recipes.index')"
                    class="hidden text-sm md:flex shrink-0 items-center gap-2 text-primary font-semibold hover:gap-3 transition-all duration-300"
                >
                    View All Recipes
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M13 7l5 5m0 0l-5 5m5-5H6" />
                    </svg>
                </Link>
            </div>

            <!-- Swipeable row on phones, grid from tablets up -->
            <div class="recipe-row flex gap-3 overflow-x-auto snap-x snap-mandatory scroll-px-4 -mx-4 px-4 md:mx-0 md:px-0 md:scroll-px-0 md:grid md:grid-cols-3 lg:grid-cols-5 md:gap-6 md:overflow-visible">
                <div
                    v-for="recipe in section.recipes"
                    :key="recipe.id"
                    class="w-[44%] shrink-0 snap-start md:w-auto"
                >
                    <RecipeCard :recipe="recipe" />
                </div>
            </div>

            <div class="mt-8 text-center md:hidden">
                <Link
                    :href="route('recipes.index')"
                    class="inline-flex text-[12px] items-center justify-center px-8 py-3 bg-gray-50 text-gray-900 font-bold rounded-lg border border-gray-200 active:bg-gray-100 transition-colors w-full sm:w-auto"
                >
                    View All Recipes
                </Link>
            </div>
        </div>
    </section>
</template>

<style scoped>
.recipe-row {
    scrollbar-width: none;
}
.recipe-row::-webkit-scrollbar {
    display: none;
}
</style>
