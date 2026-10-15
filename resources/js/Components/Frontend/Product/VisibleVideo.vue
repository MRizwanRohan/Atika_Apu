<script setup>
import { computed, onMounted, onUnmounted, ref } from "vue";

const props = defineProps({
    src: String,
    poster: String,
    imageAttributes: { type: Object, default: () => ({}) },
    enabled: { type: Boolean, default: true },
});
const container = ref(null);
const posterImage = ref(null);
const visible = ref(false);
const pageVisible = ref(true);
const allowPreview = ref(true);
const playing = computed(() => props.enabled && visible.value && pageVisible.value && allowPreview.value);
let observer;
const onVisibility = () => { pageVisible.value = !document.hidden; };

onMounted(() => {
    allowPreview.value = !navigator.connection?.saveData && !window.matchMedia("(prefers-reduced-motion: reduce)").matches;
    onVisibility();
    document.addEventListener("visibilitychange", onVisibility);
    if ("IntersectionObserver" in window) {
        observer = new IntersectionObserver(([entry]) => { visible.value = entry.isIntersecting; }, { threshold: 0.25 });
        observer.observe(container.value);
    }
});
onUnmounted(() => {
    observer?.disconnect();
    document.removeEventListener("visibilitychange", onVisibility);
});
</script>

<template>
    <div ref="container" class="relative w-full h-full bg-gray-100">
        <img v-if="poster" ref="posterImage" :src="poster" :srcset="imageAttributes.srcset" sizes="(min-width: 768px) 240px, 45vw"
            :width="imageAttributes.width" :height="imageAttributes.height" alt="" loading="lazy" decoding="async" class="absolute inset-0 w-full h-full object-cover" />
        <!-- Removing the media element when hidden also stops background buffering. -->
        <video v-if="playing" :src="src" :poster="posterImage?.currentSrc || poster" class="relative w-full h-full object-cover" preload="none" autoplay muted loop playsinline />
    </div>
</template>
