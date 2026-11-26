<script setup>
import { Link } from "@inertiajs/vue3";

defineProps({
    post: { type: Object, required: true },
});

const formatDate = (value) =>
    value ? new Date(value).toLocaleDateString("en-GB", { day: "numeric", month: "short", year: "numeric" }) : "";
</script>

<template>
    <Link
        :href="route('wellness.show', post.slug)"
        class="group flex h-full flex-col overflow-hidden rounded-2xl border border-primary bg-white transition duration-300 hover:-translate-y-1 hover:shadow-[0_12px_28px_rgba(0,0,0,0.10)]"
    >
        <div class="aspect-[16/10] overflow-hidden bg-gray-100">
            <img
                v-if="post.featured_image"
                :src="post.featured_image"
                :alt="post.title"
                loading="lazy"
                decoding="async"
                class="h-full w-full object-cover transition-transform duration-500 group-hover:scale-105"
            />
        </div>
        <div class="flex flex-1 flex-col p-5">
            <p class="text-xs font-semibold uppercase tracking-wider text-primary">{{ formatDate(post.published_at) }}</p>
            <h3 class="mt-2 font-serif text-lg font-semibold leading-snug text-gray-900 group-hover:text-primary">{{ post.title }}</h3>
            <p v-if="post.excerpt" class="mt-2 line-clamp-3 text-sm leading-relaxed text-gray-600">{{ post.excerpt }}</p>
            <span class="mt-auto pt-4 text-sm font-semibold text-primary">Read more →</span>
        </div>
    </Link>
</template>
