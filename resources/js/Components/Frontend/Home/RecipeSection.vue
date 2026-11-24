<script setup>
import { computed } from "vue";
import { Link } from "@inertiajs/vue3";
import RecipeBanner from "@/Components/Frontend/Recipe/RecipeBanner.vue";
import RecipeCard from "@/Components/Frontend/Recipe/RecipeCard.vue";

const props = defineProps({
    section: { type: Object, required: true },
});

// The last word of the title is shown in the highlight color.
const titleParts = computed(() => {
    const title = (props.section.title || "").trim();
    const cut = title.lastIndexOf(" ");
    return cut === -1 ? ["", title] : [title.slice(0, cut), title.slice(cut + 1)];
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

        <div v-if="section.recipes.length" class="container mx-auto px-4 py-8 md:py-12">
            <div
                class="rounded-[28px] p-5 sm:p-8 lg:p-10"
                style="background-color: color-mix(in srgb, var(--primary-color) 7%, white)"
            >
                <div class="grid items-center gap-7 lg:grid-cols-[minmax(240px,310px)_minmax(0,1fr)] lg:gap-10">
                    <div>
                        <p class="text-xs font-semibold uppercase tracking-[0.14em] text-primary md:text-[13px]">
                            {{ section.label }}
                        </p>
                        <h2 class="mt-3 font-serif text-3xl leading-[1.15] tracking-tight text-gray-900 sm:text-4xl">
                            {{ titleParts[0] }}
                            <span class="text-primary">{{ titleParts[1] }}</span>
                        </h2>
                        <p v-if="section.description" class="mt-4 max-w-sm text-sm leading-relaxed text-gray-700 md:text-[15px]">
                            {{ section.description }}
                        </p>
                        <Link
                            :href="route('recipes.index')"
                            class="mt-6 inline-flex h-11 items-center gap-2 rounded-full bg-primary px-6 text-sm font-semibold text-white transition-opacity hover:opacity-90 md:h-12"
                        >
                            Explore Recipes
                            <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                <path d="M5 12h14M13 6l6 6-6 6" />
                            </svg>
                        </Link>
                    </div>

                    <!-- Swipeable row on phones, 4 across from tablets up -->
                    <div class="min-w-0">
                        <div class="recipe-row -mx-5 flex snap-x snap-mandatory gap-3 overflow-x-auto px-5 py-2 scroll-px-5 sm:-mx-8 sm:px-8 sm:scroll-px-8 md:mx-0 md:grid md:grid-cols-4 md:gap-4 md:overflow-visible md:px-0 md:scroll-px-0 lg:gap-5">
                            <div
                                v-for="recipe in section.recipes"
                                :key="recipe.id"
                                class="w-[46%] shrink-0 snap-start sm:w-[34%] md:w-auto"
                            >
                                <RecipeCard :recipe="recipe" />
                            </div>
                        </div>
                    </div>
                </div>
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
