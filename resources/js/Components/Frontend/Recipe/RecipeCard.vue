<script setup>
import { Link } from "@inertiajs/vue3";

defineProps({
    recipe: { type: Object, required: true },
    sizes: { type: String, default: "(min-width: 1024px) 240px, (min-width: 768px) 33vw, 50vw" },
});
</script>

<template>
    <Link :href="route('recipes.show', recipe.slug)" class="group block">
        <div class="aspect-[4/3] overflow-hidden rounded-xl bg-gray-100">
            <img
                :src="recipe.image"
                :srcset="recipe.responsive_image?.srcset"
                :sizes="sizes"
                :width="recipe.responsive_image?.width"
                :height="recipe.responsive_image?.height"
                :alt="recipe.title"
                loading="lazy"
                decoding="async"
                class="w-full h-full object-cover transition-transform duration-500 group-hover:scale-105"
            />
        </div>
        <h3 class="mt-3 text-sm md:text-base font-semibold text-gray-900 leading-snug group-hover:text-primary transition-colors">
            {{ recipe.title }}
        </h3>
        <p v-if="recipe.subtitle" class="mt-0.5 text-xs md:text-sm text-gray-600 leading-snug">
            {{ recipe.subtitle }}
        </p>
    </Link>
</template>
