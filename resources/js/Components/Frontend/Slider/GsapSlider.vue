<template>
    <div
        class="relative w-full overflow-hidden group select-none cursor-grab active:cursor-grabbing"
        @mouseenter="stopAutoplay"
        @mouseleave="startAutoplay"
        @mousedown="handleMouseDown"
        @mousemove="handleMouseMove"
        @mouseup="handleMouseUp"
        @touchstart="handleTouchStart"
        @touchmove="handleTouchMove"
        @touchend="handleTouchEnd"
    >
        <!-- Slides -->
        <div class="relative w-full">
            <div
                v-for="(slide, index) in sliders"
                :key="slide.id"
                :class="['w-full', index === 0 ? 'relative' : 'absolute inset-0']"
                :ref="el => slideRefs[index] = el"
            >
                <img
                    :src="slide.image"
                    class="w-full h-auto object-cover pointer-events-none"
                    draggable="false"
                    :fetchpriority="index === 0 ? 'high' : 'auto'"
                    :loading="index === 0 ? 'eager' : 'lazy'"
                    :ref="el => imageRefs[index] = el"
                />
            </div>
        </div>

        <!-- Pagination -->
        <div class="absolute bottom-6 left-1/2 -translate-x-1/2 z-20 flex gap-4">
            <button
                v-for="(_, index) in sliders"
                :key="index"
                @click="goToSlide(index)"
                class="relative w-2 h-2 rounded-full bg-white"
                :class="currentSlide === index ? 'bg-white' : ''"
            >
                <!-- SVG Border Progress -->
                <svg
                    class="absolute -inset-1.5 w-5 h-5 rotate-[-90deg]"
                    viewBox="0 0 36 36"
                >
                    <!-- Track -->
                    <circle
                        cx="18"
                        cy="18"
                        r="16"
                        fill="none"
                        stroke="rgba(255,255,255,0.0)"
                        stroke-width="2"
                    />
                    <!-- Progress -->
                    <circle
                        cx="18"
                        cy="18"
                        r="16"
                        fill="none"
                        stroke="white"
                        stroke-width="2"
                        stroke-linecap="round"
                        :ref="el => progressRefs[index] = el"
                    />
                </svg>
            </button>
        </div>
    </div>
</template>


<script setup>
import { ref, onMounted, onUnmounted, nextTick } from "vue";
import gsap from "gsap";

/* ================= PROPS ================= */
const props = defineProps({
    sliders: {
        type: Array,
        required: true,
    },
});

/* ================= REFS ================= */
const currentSlide = ref(0);
const isAnimating = ref(false);

const slideRefs = ref([]);
const imageRefs = ref([]);
const progressRefs = ref([]);

/* ================= TIMING (SOURCE OF TRUTH) ================= */
const SLIDE_ANIMATION_TIME = 300; // ms (GSAP duration 0.3s)
const SLIDE_VISIBLE_TIME = 4700;  // ms (progress duration)

/* SVG progress math */
const RADIUS = 16;
const CIRCUMFERENCE = 2 * Math.PI * RADIUS;

/* autoplay controller */
let autoplayTimeout = null;

/* ================= PROGRESS ================= */
const resetProgress = () => {
    progressRefs.value.forEach(circle => {
        if (!circle) return;
        circle.style.transition = "none";
        circle.style.strokeDasharray = CIRCUMFERENCE;
        circle.style.strokeDashoffset = CIRCUMFERENCE;
    });
};

const startProgress = () => {
    resetProgress();

    const active = progressRefs.value[currentSlide.value];
    if (!active) return;

    // force reflow
    active.getBoundingClientRect();

    active.style.transition =
        `stroke-dashoffset ${SLIDE_VISIBLE_TIME}ms linear`;
    active.style.strokeDashoffset = "0";
};

/* ================= AUTOPLAY (NO BUFFER) ================= */
const startAutoplay = () => {
    stopAutoplay();
    if (props.sliders.length <= 1) return;

    startProgress();

    autoplayTimeout = setTimeout(() => {
        next(); // ONLY trigger slide
    }, SLIDE_VISIBLE_TIME);
};

const stopAutoplay = () => {
    if (autoplayTimeout) {
        clearTimeout(autoplayTimeout);
        autoplayTimeout = null;
    }
    resetProgress();
};

/* ================= SLIDER CONTROLS ================= */
const next = () => {
    if (isAnimating.value) return;
    animateSlide((currentSlide.value + 1) % props.sliders.length);
};

const prev = () => {
    if (isAnimating.value) return;
    animateSlide(
        (currentSlide.value - 1 + props.sliders.length) %
            props.sliders.length
    );
};

const goToSlide = (index) => {
    if (index === currentSlide.value || isAnimating.value) return;
    animateSlide(index);
};

/* ================= SLIDE ANIMATION ================= */
const animateSlide = (nextIndex) => {
    if (isAnimating.value) return;
    isAnimating.value = true;

    const current = slideRefs.value[currentSlide.value];
    const nextSlide = slideRefs.value[nextIndex];
    const nextImage = imageRefs.value[nextIndex];

    if (!current || !nextSlide) {
        isAnimating.value = false;
        return;
    }

    gsap.set(nextSlide, {
        autoAlpha: 1,
        zIndex: 2,
        clipPath: "polygon(0 0,0 0,0 100%,0 100%)",
    });

    gsap.set(current, { zIndex: 1 });

    gsap.to(nextSlide, {
        clipPath: "polygon(0 0,100% 0,100% 100%,0 100%)",
        duration: SLIDE_ANIMATION_TIME / 1000,
        ease: "power2.inOut",
        onComplete: () => {
            gsap.set(current, { autoAlpha: 0 });

            currentSlide.value = nextIndex;
            isAnimating.value = false;

            // 🔥 PERFECT SYNC POINT (NO BUFFER)
            startAutoplay();
        },
    });

    if (nextImage) {
        gsap.fromTo(
            nextImage,
            { scale: 1.2 },
            { scale: 1, duration: 2.5, ease: "power1.out" }
        );
    }
};

/* ================= DRAG / TOUCH ================= */
let startX = 0;
let endX = 0;

const handleMouseDown = (e) => {
    stopAutoplay();
    startX = e.clientX;
};

const handleMouseMove = (e) => {
    endX = e.clientX;
};

const handleMouseUp = () => {
    const diff = startX - endX;
    if (Math.abs(diff) > 50) {
        diff > 0 ? next() : prev();
    } else {
        startAutoplay();
    }
};

const handleTouchStart = (e) => {
    stopAutoplay();
    startX = e.touches[0].clientX;
};

const handleTouchMove = (e) => {
    endX = e.touches[0].clientX;
};

const handleTouchEnd = handleMouseUp;

/* ================= INIT ================= */
onMounted(() => {
    nextTick(() => {
        slideRefs.value.forEach((el, i) => {
            gsap.set(el, { autoAlpha: i === 0 ? 1 : 0 });
        });
        startAutoplay();
    });
});

onUnmounted(() => {
    stopAutoplay();
    gsap.killTweensOf(slideRefs.value);
    gsap.killTweensOf(imageRefs.value);
});
</script>


