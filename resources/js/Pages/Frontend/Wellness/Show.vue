<script setup>
import { computed } from "vue";
import { Head, Link } from "@inertiajs/vue3";
import DOMPurify from "dompurify";
import FrontendLayout from "@/Layouts/FrontendLayout.vue";
import PostCard from "@/Components/Frontend/Wellness/PostCard.vue";

defineOptions({ layout: FrontendLayout });

const props = defineProps({
    post: { type: Object, required: true },
    morePosts: { type: Array, default: () => [] },
});

const content = computed(() => (props.post.content ? DOMPurify.sanitize(props.post.content) : ""));
const date = computed(() =>
    props.post.published_at
        ? new Date(props.post.published_at).toLocaleDateString("en-GB", { day: "numeric", month: "long", year: "numeric" })
        : "",
);
</script>

<template>
    <Head :title="post.meta_title || post.title">
        <meta name="description" :content="post.meta_description || post.excerpt || post.title" />
    </Head>

    <article class="container mx-auto px-4 py-8 md:py-14">
        <div class="mx-auto max-w-3xl">
            <nav class="mb-4 text-xs text-gray-500 md:text-sm" aria-label="Breadcrumb">
                <Link :href="route('home')" class="hover:text-primary">Home</Link>
                <span class="mx-2">/</span>
                <Link :href="route('wellness.index')" class="hover:text-primary">Wellness</Link>
            </nav>

            <p class="text-xs font-semibold uppercase tracking-wider text-primary md:text-sm">
                {{ date }}<template v-if="post.author_name"> · {{ post.author_name }}</template>
            </p>
            <h1 class="mt-2 font-serif text-3xl font-semibold leading-tight md:text-5xl" style="color: color-mix(in srgb, var(--primary-color) 45%, black)">
                {{ post.title }}
            </h1>
            <p v-if="post.excerpt" class="mt-4 text-base leading-relaxed text-gray-600 md:text-lg">{{ post.excerpt }}</p>

            <img
                v-if="post.featured_image"
                :src="post.featured_image"
                :alt="post.title"
                class="mt-8 aspect-[16/9] w-full rounded-3xl object-cover"
            />

            <div v-if="content" class="prose prose-lg mt-8 max-w-none rounded-3xl bg-white/90 p-6 md:p-10" v-html="content" />
        </div>

        <section v-if="morePosts.length" class="mt-14 md:mt-20">
            <h2 class="font-serif text-2xl font-semibold text-gray-900 md:text-3xl">More from Wellness</h2>
            <div class="mt-6 grid gap-5 sm:grid-cols-2 md:gap-7 lg:grid-cols-3">
                <PostCard v-for="item in morePosts" :key="item.id" :post="item" />
            </div>
        </section>
    </article>
</template>
