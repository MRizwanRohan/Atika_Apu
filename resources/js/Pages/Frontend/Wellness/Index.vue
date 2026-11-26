<script setup>
import { Head, Link } from "@inertiajs/vue3";
import FrontendLayout from "@/Layouts/FrontendLayout.vue";
import PostCard from "@/Components/Frontend/Wellness/PostCard.vue";

defineOptions({ layout: FrontendLayout });

defineProps({
    posts: { type: Object, required: true },
});
</script>

<template>
    <Head title="Wellness">
        <meta name="description" content="Wellness tips, healthy eating and stories from our kitchen." />
    </Head>

    <section class="container mx-auto px-4 py-8 md:py-14">
        <nav class="mb-4 text-xs text-gray-500 md:text-sm" aria-label="Breadcrumb">
            <Link :href="route('home')" class="hover:text-primary">Home</Link>
            <span class="mx-2">/</span>
            <span class="text-gray-900">Wellness</span>
        </nav>

        <p class="text-xs font-semibold uppercase tracking-[0.16em] text-primary md:text-sm">Eat well, live well</p>
        <h1 class="mt-2 font-serif text-3xl font-semibold md:text-5xl" style="color: color-mix(in srgb, var(--primary-color) 45%, black)">
            Wellness
        </h1>

        <div v-if="posts.data.length" class="mt-8 grid gap-5 sm:grid-cols-2 md:mt-10 md:gap-7 lg:grid-cols-3">
            <PostCard v-for="post in posts.data" :key="post.id" :post="post" />
        </div>
        <p v-else class="mt-10 rounded-2xl bg-white/80 p-10 text-center text-gray-500">
            New wellness articles are coming soon.
        </p>

        <nav v-if="posts.last_page > 1" class="mt-10 flex flex-wrap justify-center gap-2" aria-label="Pagination">
            <template v-for="link in posts.links" :key="link.label">
                <Link
                    v-if="link.url"
                    :href="link.url"
                    class="min-w-[2.5rem] rounded-full border px-3 py-2 text-center text-sm font-semibold transition-colors"
                    :class="link.active ? 'border-primary bg-primary text-white' : 'border-gray-200 bg-white text-gray-700 hover:border-primary hover:text-primary'"
                    v-html="link.label"
                />
            </template>
        </nav>
    </section>
</template>
