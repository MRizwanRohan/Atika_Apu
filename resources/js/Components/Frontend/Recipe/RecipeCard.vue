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
        class="group block h-full overflow-hidden rounded-2xl border border-black/5 bg-white shadow-[0_6px_20px_rgba(0,0,0,0.06)] transition duration-300 hover:-translate-y-1 hover:shadow-[0_12px_28px_rgba(0,0,0,0.12)]"
    >
        <div class="aspect-[6/7] overflow-hidden bg-gray-100">
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
        <div class="px-3 py-3.5 text-center md:py-4">
            <h3 class="text-sm font-semibold leading-snug text-gray-900 md:text-[15px]">
                {{ recipe.title }}
            </h3>
            <p v-if="recipe.subtitle" class="mt-0.5 text-xs leading-snug text-gray-600 md:text-sm">
                {{ recipe.subtitle }}
            </p>
        </div>
    </Link>
</template>
