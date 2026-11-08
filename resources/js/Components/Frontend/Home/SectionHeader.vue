<script setup>
import { ref } from "vue";
import { Link } from "@inertiajs/vue3";

// Hide the title until its font is ready so it never swaps in from a fallback font.
const TITLE_FONT = '700 1em "Oswald"';
const fontReady = ref(!document.fonts || document.fonts.check(TITLE_FONT));
if (!fontReady.value) {
    const reveal = () => (fontReady.value = true);
    document.fonts.load(TITLE_FONT).then(reveal, reveal);
    setTimeout(reveal, 2000);
}

defineProps({
    title: { type: String, required: true },
    subtitle: { type: String, default: "" },
    href: { type: String, default: "" },
    showNav: { type: Boolean, default: false },
    isBeginning: { type: Boolean, default: true },
    isEnd: { type: Boolean, default: false },
});

defineEmits(["prev", "next"]);
</script>

<template>
    <div class="relative overflow-hidden flex items-end justify-between gap-3 mb-4 md:mb-6">
        <div
            class="relative min-w-0 pt-2 md:pt-4 transition-opacity duration-200"
            :class="fontReady ? 'opacity-100' : 'opacity-0'"
        >
            <span
                aria-hidden="true"
                class="pointer-events-none select-none absolute top-0 left-0 font-script text-4xl md:text-6xl leading-none whitespace-nowrap text-gray-900/[0.06]"
            >
                {{ title }}
            </span>

            <div class="relative flex items-center gap-2 md:gap-3">
                <h2
                    class="font-display font-bold uppercase leading-tight tracking-wide text-primary text-xl sm:text-3xl md:text-4xl break-words"
                >
                    {{ title }}
                </h2>
                <svg
                    aria-hidden="true"
                    class="hidden sm:block shrink-0 w-16 h-5 md:w-[88px] md:h-7 text-primary"
                    viewBox="0 0 88 28"
                    fill="none"
                    stroke="currentColor"
                    stroke-width="1.6"
                    stroke-linecap="round"
                    stroke-linejoin="round"
                >
                    <path d="M2 16h42" />
                    <path d="M44 16c9 0 18-3 26-9" />
                    <path d="M57 13c-1-6 3-10 9-11 1 6-3 10-9 11z" />
                    <path d="M69 8c4-5 10-7 17-5-3 6-9 8-17 5z" />
                    <path d="M53 15c-4 4-9 6-14 5 2-5 8-7 14-5z" />
                </svg>
            </div>

            <p v-if="subtitle" class="relative mt-1 text-sm md:text-base text-gray-600">
                {{ subtitle }}
            </p>
        </div>

        <div class="flex items-center gap-2 md:gap-3 shrink-0">
            <Link
                v-if="href"
                :href="href"
                class="hidden md:inline-flex mr-1 text-sm font-semibold text-primary hover:underline underline-offset-4"
            >
                View All
            </Link>

            <template v-if="showNav">
                <button
                    type="button"
                    aria-label="Previous"
                    :disabled="isBeginning"
                    class="nav-btn"
                    @click="$emit('prev')"
                >
                    <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M15 18l-6-6 6-6" />
                    </svg>
                </button>
                <button
                    type="button"
                    aria-label="Next"
                    :disabled="isEnd"
                    class="nav-btn"
                    @click="$emit('next')"
                >
                    <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M9 18l6-6-6-6" />
                    </svg>
                </button>
            </template>
        </div>
    </div>
</template>

<style scoped>
.nav-btn {
    @apply flex items-center justify-center w-9 h-9 md:w-11 md:h-11 rounded-full border border-primary text-primary bg-white transition-colors duration-200 hover:bg-primary hover:text-white disabled:border-gray-200 disabled:text-gray-300 disabled:bg-white disabled:cursor-not-allowed;
}
</style>
