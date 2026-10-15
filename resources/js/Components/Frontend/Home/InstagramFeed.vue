<script setup>
defineProps({
    instagramFeeds: {
        type: Array,
        default: () => [],
    },
    settings: {
        type: Object,
        default: () => ({}),
    }
});
</script>

<template>
    <section v-if="instagramFeeds && instagramFeeds.length > 0" class="py-12 bg-gray-50/50">
        <div class="w-full mx-auto px-4">
            <!-- Section Header -->
            <div class="text-center mb-10">
                <h2 class="text-2xl md:text-3xl font-bold text-gray-900 flex items-center justify-center gap-3">
                    <span class="p-2 bg-gradient-to-tr from-yellow-400 via-red-500 to-purple-600 rounded-xl text-white">
                        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="2" y="2" width="20" height="20" rx="5" ry="5"></rect><path d="M16 11.37A4 4 0 1 1 12.63 8 4 4 0 0 1 16 11.37z"></path><line x1="17.5" y1="6.5" x2="17.51" y2="6.5"></line></svg>
                    </span>
                    Follow Us on Instagram
                </h2>
                <p class="mt-3 text-gray-600 max-w-md mx-auto">
                    Get inspired by our latest collections and tagged moments from our community.
                </p>
            </div>

            <!-- Instagram Grid -->
            <div class="grid grid-cols-3 md:grid-cols-6 lg:grid-cols-8 gap-4">
                <div
                    v-for="feed in instagramFeeds"
                    :key="feed.id"
                    class="group relative aspect-9/16 overflow-hidden rounded-2xl bg-gray-200"
                >
                    <img loading="lazy" decoding="async"
                        :src="`${feed.image}`"
                        :srcset="feed.responsive_image?.srcset"
                        sizes="(min-width: 1024px) 300px, 50vw"
                        :width="feed.responsive_image?.width"
                        :height="feed.responsive_image?.height"
                        :alt="`Instagram Post ${feed.id}`"
                        class="h-full w-full object-cover transition-transform duration-500 group-hover:scale-110"
                    />

                    <!-- Overlay -->
                    <a
                        v-if="feed.link"
                        :href="feed.link"
                        target="_blank"
                        class="absolute inset-0 z-10 flex items-center justify-center bg-black/40 opacity-0 transition-opacity duration-300 group-hover:opacity-100"
                    >
                        <div class="transform translate-y-4 transition-transform duration-300 group-hover:translate-y-0">
                            <span class="bg-white/20 block backdrop-blur-md p-3 rounded-full text-white hover:bg-white/30 transition-colors">
                                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="w-6 h-6"><path d="M18 13v6a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h6"></path><polyline points="15 3 21 3 21 9"></polyline><line x1="10" y1="14" x2="21" y2="3"></line></svg>
                            </span>
                        </div>
                    </a>
                    <div
                        v-else
                        class="absolute inset-0 flex items-center justify-center bg-black/40 opacity-0 transition-opacity duration-300 group-hover:opacity-100"
                    >
                        <span class="text-white">
                            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="w-8 h-8 opacity-80"><rect x="2" y="2" width="20" height="20" rx="5" ry="5"></rect><path d="M16 11.37A4 4 0 1 1 12.63 8 4 4 0 0 1 16 11.37z"></path><line x1="17.5" y1="6.5" x2="17.51" y2="6.5"></line></svg>
                        </span>
                    </div>
                </div>
            </div>

        </div>
    </section>
</template>

<style scoped>
.container {
    max-width: 1400px;
}
</style>
