<script setup>
import { ref, watch, computed } from "vue";
import { Link, usePage } from "@inertiajs/vue3";

const props = defineProps({
    isOpen: {
        type: Boolean,
        default: false,
    },
});

const emit = defineEmits(["close"]);

const page = usePage();
const navCategories = computed(() => page.props.navCategories || []);
const saucesOpen = ref(false);

// Same options and active rules as the desktop header menu.
const path = computed(() => (page.url || "/").split("?")[0]);
const active = computed(() => ({
    home: path.value === "/",
    kitchen: path.value.startsWith("/category/"),
    story: path.value === "/page/about-us",
    journal: path.value.startsWith("/recipes"),
    wellness: path.value.startsWith("/wellness"),
    shop: path.value.startsWith("/products") || path.value.startsWith("/product/"),
    contact: path.value === "/page/contact",
}));

const rowClass = (isActive) => [
    "flex-1 py-3.5 pr-2 text-[15px] font-semibold transition-colors hover:text-primary",
    isActive ? "text-primary" : "text-gray-800",
];

watch(
    () => props.isOpen,
    (open) => {
        if (!open) saucesOpen.value = false;
        // Prevent body scroll while the menu is open
        document.body.style.overflow = open ? "hidden" : "";
    },
);
</script>

<template>
    <!-- Overlay -->
    <Transition name="fade">
        <div
            v-if="isOpen"
            class="fixed inset-0 bg-black/50 z-50 lg:hidden"
            @click="emit('close')"
        ></div>
    </Transition>

    <!-- Sidebar Menu -->
    <Transition name="slide">
        <div
            v-if="isOpen"
            class="fixed top-0 left-0 h-full w-80 max-w-[85vw] bg-white z-50 shadow-xl overflow-hidden flex flex-col lg:hidden"
        >
            <div class="flex items-center justify-between p-4 border-b bg-gray-50">
                <h2 class="text-lg font-bold text-gray-900">Menu</h2>
                <button
                    @click="emit('close')"
                    class="p-2 hover:bg-gray-200 rounded-full transition-colors"
                    aria-label="Close menu"
                >
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>

            <nav aria-label="Main" class="flex-1 overflow-y-auto px-4 py-2">
                <div class="flex items-center border-b border-gray-100">
                    <Link href="/" :class="rowClass(active.home)" @click="emit('close')">Home</Link>
                </div>

                <div class="border-b border-gray-100">
                    <div class="flex items-center">
                        <Link :href="route('products.index')" :class="rowClass(active.kitchen)" @click="emit('close')">Our Kitchen</Link>
                        <button
                            v-if="navCategories.length"
                            type="button"
                            class="-mr-2 p-3 text-gray-500 transition-colors hover:text-primary"
                            :aria-expanded="saucesOpen"
                            aria-label="Show categories"
                            @click="saucesOpen = !saucesOpen"
                        >
                            <svg
                                xmlns="http://www.w3.org/2000/svg"
                                class="h-5 w-5 transition-transform"
                                :class="{ 'rotate-180': saucesOpen }"
                                fill="none"
                                viewBox="0 0 24 24"
                                stroke="currentColor"
                                stroke-width="2"
                            >
                                <path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7" />
                            </svg>
                        </button>
                    </div>

                    <div v-if="saucesOpen" class="mb-2 rounded-xl bg-gray-50 py-1">
                        <Link
                            v-for="category in navCategories"
                            :key="category.id"
                            :href="route('category.show', category.slug)"
                            class="block px-3 py-2 text-sm text-gray-700 transition-colors hover:text-primary"
                            @click="emit('close')"
                        >
                            {{ category.name }}
                        </Link>
                    </div>
                </div>

                <div class="flex items-center border-b border-gray-100">
                    <Link :href="route('pages.show', 'about-us')" :class="rowClass(active.story)" @click="emit('close')">Our Story</Link>
                </div>

                <div class="flex items-center border-b border-gray-100">
                    <Link :href="route('recipes.index')" :class="rowClass(active.journal)" @click="emit('close')">Food Journal</Link>
                </div>

                <div class="flex items-center border-b border-gray-100">
                    <Link :href="route('wellness.index')" :class="rowClass(active.wellness)" @click="emit('close')">Wellness</Link>
                </div>

                <div class="flex items-center border-b border-gray-100">
                    <Link :href="route('products.index')" :class="rowClass(active.shop)" @click="emit('close')">Shop</Link>
                </div>

                <div class="flex items-center">
                    <Link :href="route('pages.show', 'contact')" :class="rowClass(active.contact)" @click="emit('close')">Contact</Link>
                </div>
            </nav>
        </div>
    </Transition>
</template>

<style scoped>
.fade-enter-active,
.fade-leave-active {
    transition: opacity 0.3s ease;
}

.fade-enter-from,
.fade-leave-to {
    opacity: 0;
}

.slide-enter-active,
.slide-leave-active {
    transition: transform 0.3s ease;
}

.slide-enter-from,
.slide-leave-to {
    transform: translateX(-100%);
}
</style>
