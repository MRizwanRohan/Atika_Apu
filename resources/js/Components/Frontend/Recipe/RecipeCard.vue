<script setup>
import { Link } from "@inertiajs/vue3";

defineProps({
    recipe: { type: Object, required: true },
    sizes: { type: String, default: "(min-width: 1280px) 220px, (min-width: 768px) 24vw, 46vw" },
});
</script>

<template>
    <Link
        :href="route('recipes.show', recipe.slug)"
        class="group block h-full"
    >
        <div class="aspect-[6/7] overflow-hidden rounded-2xl border border-black/5 bg-gray-100 shadow-sm md:rounded-3xl">
            <img
                :src="recipe.image"
                :srcset="recipe.responsive_image?.srcset"
                :sizes="sizes"
                :width="recipe.responsive_image?.width"
                :height="recipe.responsive_image?.height"
                :alt="recipe.title"
                loading="lazy"
                decoding="async"
                class="h-full w-full object-cover transition-transform duration-500 group-hover:scale-105"
            />
        </div>
        <div class="px-1 pt-3 text-center">
            <h3 class="text-sm font-bold leading-snug text-gray-900 md:text-[15px]">
                {{ recipe.title }}
            </h3>
            <p v-if="recipe.subtitle" class="mt-0.5 text-xs leading-snug text-gray-600 md:text-sm">
                {{ recipe.subtitle }}
            </p>
        </div>
    </Link>
</template>
