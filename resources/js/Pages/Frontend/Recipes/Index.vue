<script setup>
import { computed } from "vue";
import { Head, Link } from "@inertiajs/vue3";
import FrontendLayout from "@/Layouts/FrontendLayout.vue";
import ArtworkBackground from "@/Components/Frontend/Home/ArtworkBackground.vue";
import paperTexture from "@/../images/paper-texture.webp";

defineOptions({ layout: FrontendLayout });

const props = defineProps({
    recipes: { type: Object, required: true },
    section: { type: Object, default: null },
});

// On the first page the newest recipe is shown large, journal-style; the rest go in the grid.
const featured = computed(() => (props.recipes.current_page === 1 ? props.recipes.data[0] : null));
const rest = computed(() => (featured.value ? props.recipes.data.slice(1) : props.recipes.data));

const dark = "color-mix(in srgb, var(--primary-color) 45%, black)";
const darkButton = "color-mix(in srgb, var(--primary-color) 70%, black)";
</script>

<template>
    <Head title="Food Journal">
        <meta name="description" content="Recipes from our kitchen: everyday Bengali cooking made extraordinary with Humming Chef sauces." />
    </Head>

    <!-- Hero on the paper artwork; on desktop it runs up behind the transparent header -->
    <ArtworkBackground tag="section" class="lg:-mt-24 lg:pt-24">
        <div class="container mx-auto px-4 pb-14 pt-10 text-center md:pb-20 md:pt-14">
            <nav class="mb-4 text-xs text-[#6b4a2b] md:text-sm" aria-label="Breadcrumb">
                <Link :href="route('home')" class="hover:text-primary">Home</Link>
                <span class="mx-2">/</span>
                <span class="text-gray-900">Food Journal</span>
            </nav>
            <p class="flex items-center justify-center gap-2 text-xs font-semibold uppercase tracking-[0.2em] text-primary md:text-sm">
                <span class="h-px w-8 bg-current opacity-50" aria-hidden="true" />
                Food Journal
                <span class="h-px w-8 bg-current opacity-50" aria-hidden="true" />
            </p>
            <h1 class="mx-auto mt-3 max-w-3xl font-serif text-4xl font-semibold leading-tight md:text-6xl" :style="{ color: dark }">
                Recipes from <span class="text-primary">Ma's kitchen.</span>
            </h1>
            <p class="mx-auto mt-4 max-w-xl text-sm leading-relaxed text-gray-800 md:text-base">
                Everyday Bengali cooking, the way it was made at home, with a spoonful of Humming Chef.
            </p>
        </div>
    </ArtworkBackground>

    <div class="bg-[#f6d999] pb-16 md:pb-24" :style="{ backgroundImage: `url(${paperTexture})`, backgroundSize: '640px auto' }">
        <div class="container mx-auto px-4">
            <!-- Featured recipe, floating on the seam like the homepage bars -->
            <Link
                v-if="featured"
                :href="route('recipes.show', featured.slug)"
                class="group relative z-10 -mt-8 grid overflow-hidden rounded-3xl border border-white/70 bg-[#fdf4dd]/85 shadow-[0_14px_40px_rgba(0,0,0,0.12)] backdrop-blur-md md:-mt-12 md:h-[400px] md:grid-cols-[1.15fr_1fr]"
            >
                <div class="aspect-[4/3] overflow-hidden md:aspect-auto md:h-full">
                    <img
                        :src="featured.image"
                        :srcset="featured.responsive_image?.srcset"
                        sizes="(min-width: 768px) 55vw, 100vw"
                        :alt="featured.title"
                        class="h-full w-full object-cover transition-transform duration-700 group-hover:scale-105"
                    />
                </div>
                <div class="flex flex-col justify-center p-6 md:p-10 lg:p-14">
                    <p class="text-xs font-semibold uppercase tracking-[0.2em] text-primary">Featured recipe</p>
                    <h2 class="mt-3 font-serif text-3xl font-semibold leading-tight md:text-4xl" :style="{ color: dark }">
                        {{ featured.title }}
                    </h2>
                    <p v-if="featured.subtitle" class="mt-2 text-base text-gray-700 md:text-lg">{{ featured.subtitle }}</p>
                    <span
                        class="mt-7 inline-flex h-12 w-fit items-center gap-2 rounded-full px-7 text-sm font-semibold text-white transition-opacity group-hover:opacity-90"
                        :style="{ backgroundColor: darkButton }"
                    >
                        Read the recipe
                        <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                            <path d="M5 12h14M13 6l6 6-6 6" />
                        </svg>
                    </span>
                </div>
            </Link>

            <!-- More recipes -->
            <template v-if="rest.length">
                <div class="mb-6 mt-14 flex items-center gap-4 md:mb-10 md:mt-20">
                    <h2 class="shrink-0 font-serif text-2xl font-semibold md:text-3xl" :style="{ color: dark }">More from the journal</h2>
                    <span class="h-px flex-1 bg-[#6b4a2b]/25" aria-hidden="true" />
                </div>

                <div class="grid grid-cols-2 gap-x-4 gap-y-10 md:grid-cols-3 md:gap-x-8 md:gap-y-14">
                    <Link
                        v-for="recipe in rest"
                        :key="recipe.id"
                        :href="route('recipes.show', recipe.slug)"
                        class="group block"
                    >
                        <div class="aspect-[4/3] overflow-hidden rounded-2xl md:rounded-3xl">
                            <img
                                :src="recipe.image"
                                :srcset="recipe.responsive_image?.srcset"
                                sizes="(min-width: 768px) 33vw, 50vw"
                                :alt="recipe.title"
                                loading="lazy"
                                decoding="async"
                                class="h-full w-full object-cover transition-transform duration-500 group-hover:scale-105"
                            />
                        </div>
                        <h3 class="mt-4 font-serif text-lg font-semibold leading-snug md:text-2xl" :style="{ color: dark }">
                            {{ recipe.title }}
                        </h3>
                        <p v-if="recipe.subtitle" class="mt-1 text-sm text-gray-700 md:text-base">{{ recipe.subtitle }}</p>
                        <span class="mt-3 inline-flex items-center gap-1.5 text-sm font-semibold text-primary">
                            Read recipe
                            <svg class="h-4 w-4 transition-transform group-hover:translate-x-1" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                <path d="M5 12h14M13 6l6 6-6 6" />
                            </svg>
                        </span>
                    </Link>
                </div>
            </template>

            <p v-if="!recipes.data.length" class="pt-16 text-center text-gray-700">No recipes yet. Check back soon!</p>

            <nav v-if="recipes.last_page > 1" class="mt-14 flex flex-wrap justify-center gap-2" aria-label="Pagination">
                <template v-for="(link, i) in recipes.links" :key="i">
                    <Link
                        v-if="link.url"
                        :href="link.url"
                        class="flex h-10 min-w-10 items-center justify-center rounded-full border px-3 text-sm transition-colors"
                        :class="link.active ? 'border-primary bg-primary text-white' : 'border-[#6b4a2b]/30 text-gray-800 hover:border-primary hover:text-primary'"
                    >
                        <span v-html="link.label" />
                    </Link>
                    <span
                        v-else
                        class="flex h-10 min-w-10 items-center justify-center rounded-full border border-[#6b4a2b]/15 px-3 text-sm text-gray-400"
                        v-html="link.label"
                    />
                </template>
            </nav>
        </div>
    </div>
</template>
