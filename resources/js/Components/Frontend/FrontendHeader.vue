<script setup>
import { ref, computed, watch, onMounted, onBeforeUnmount, defineAsyncComponent } from "vue";
import { Link, usePage } from "@inertiajs/vue3";
const CartSidebar = defineAsyncComponent(() => import("./CartSidebar.vue"));
import { useCartSidebar } from "@/Composables/useCartSidebar";
import SearchBar from "../Header/SearchBar.vue";
import MobileBottomNav from "./MobileBottomNav.vue";
import {
    PhMagnifyingGlass,
    PhX,
    PhHandbag,
    PhUser,
    PhShoppingCartSimple,
} from "@phosphor-icons/vue";

const props = defineProps({
    settings: {
        type: Object,
        default: () => ({}),
    },
    cart: {
        type: Object,
        default: () => ({}),
    },
});

const emit = defineEmits(["toggle-mobile-menu"]);

const page = usePage();
const {
    isCartSidebarOpen,
    toggleCart: toggleCartSidebar,
    closeCart: closeCartSidebar,
} = useCartSidebar();

const cartSidebarLoaded = ref(false);
watch(isCartSidebarOpen, (open) => { if (open) cartSidebarLoaded.value = true; }, { immediate: true });
const isSearchOpen = ref(false);

const cartCount = computed(() => page.props.cartCount || 0);
const user = computed(() => page.props.auth?.user);
const isAdmin = computed(() =>
    user.value?.roles?.some((r) => ["superadmin", "admin", "super-admin", "Admin", "Super Admin"].includes(r.name)),
);
const wishlistCount = computed(() => page.props.wishlistCount || 0);

const logoUrl = computed(() => props.settings?.media?.logo || "");
const siteName = computed(
    () => props.settings?.generalSettings?.app_name || props.settings?.site_name || "Store",
);

// Main menu. Keep the markup and classes in sync with resources/views/storefront-bootstrap.blade.php.
const navCategories = computed(() => page.props.navCategories || []);
const path = computed(() => (page.url || "/").split("?")[0]);
const isHome = computed(() => path.value === "/");
const active = computed(() => ({
    kitchen: path.value.startsWith("/category/"),
    story: path.value === "/page/about-us",
    journal: path.value.startsWith("/recipes"),
    wellness: path.value.startsWith("/wellness"),
    shop: path.value.startsWith("/products") || path.value.startsWith("/product/"),
}));

const linkClass = (isActive) => [
    "relative inline-flex h-24 items-center gap-1 text-[16px] font-medium transition-colors hover:text-primary",
    isActive ? "text-primary after:absolute after:inset-x-0 after:bottom-6 after:h-0.5 after:bg-primary" : "text-gray-800",
];

// Transparent over the page at the top; turns solid once the page scrolls under it.
const scrolled = ref(false);
const onScroll = () => { scrolled.value = window.scrollY > 24; };
// The large logo hangs over the home banner only; elsewhere it sits inside the bar.
const logoHangs = computed(() => isHome.value && !scrolled.value);
onMounted(() => { onScroll(); window.addEventListener("scroll", onScroll, { passive: true }); });
onBeforeUnmount(() => window.removeEventListener("scroll", onScroll));
</script>

<template>
    <header
        class="sticky top-0 z-50 bg-white shadow-sm font-sans transition-[background-color,box-shadow] duration-300 lg:shadow-none"
        :class="[
            isHome ? 'lg:fixed lg:inset-x-0' : '',
            scrolled ? 'lg:bg-white/20 lg:shadow-[0_4px_24px_rgba(0,0,0,0.06)] lg:backdrop-blur-xl lg:backdrop-saturate-150 lg:ring-1 lg:ring-white/30' : 'lg:bg-transparent',
        ]"
    >
        <!-- Main Header -->
        <div class="bg-white border-b border-gray-100 lg:border-0 lg:bg-transparent">
            <div class="container mx-auto px-4 lg:max-w-none lg:px-0">
                <!--
                    Desktop: no container. The left gap is kept free for the logo, which hangs
                    below the bar at the top of the page; the right side runs to the screen edge.
                -->
                <div class="relative hidden h-24 items-center pl-[clamp(200px,18vw,320px)] pr-6 lg:flex xl:pr-10">
                    <Link
                        href="/"
                        class="absolute left-[clamp(24px,4vw,72px)] flex items-start transition-all duration-300"
                        :class="logoHangs ? 'top-3' : 'top-1/2 -translate-y-1/2'"
                        :aria-label="siteName"
                    >
                        <img
                            v-if="logoUrl"
                            :src="logoUrl"
                            :alt="siteName"
                            class="w-auto max-w-[220px] object-contain transition-all duration-300"
                            :class="logoHangs ? 'h-[150px] xl:h-[180px]' : 'h-[72px]'"
                        />
                        <span v-else class="text-2xl font-black tracking-tighter text-gray-900">{{ siteName }}</span>
                    </Link>

                    <!-- Menu -->
                    <nav aria-label="Main" class="flex items-center gap-x-7 xl:gap-x-10">
                        <div class="group relative">
                            <Link :href="route('products.index')" :class="linkClass(active.kitchen)">
                                Our Kitchen
                                <svg
                                    v-if="navCategories.length"
                                    xmlns="http://www.w3.org/2000/svg"
                                    class="h-4 w-4 transition-transform duration-200 group-hover:rotate-180"
                                    fill="none"
                                    viewBox="0 0 24 24"
                                    stroke="currentColor"
                                    stroke-width="2.5"
                                    aria-hidden="true"
                                >
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7" />
                                </svg>
                            </Link>

                            <div
                                v-if="navCategories.length"
                                class="invisible absolute left-0 top-full z-50 w-60 -translate-y-4 opacity-0 transition duration-200 group-hover:visible group-hover:opacity-100 group-focus-within:visible group-focus-within:opacity-100"
                            >
                                <div class="rounded-2xl border border-gray-100 bg-white py-2 shadow-xl">
                                    <Link
                                        v-for="category in navCategories"
                                        :key="category.id"
                                        :href="route('category.show', category.slug)"
                                        class="block px-5 py-2.5 text-[15px] font-medium text-gray-800 transition-colors hover:bg-gray-50 hover:text-primary"
                                    >
                                        {{ category.name }}
                                    </Link>
                                </div>
                            </div>
                        </div>

                        <Link :href="route('pages.show', 'about-us')" :class="linkClass(active.story)">Our Story</Link>
                        <Link :href="route('recipes.index')" :class="linkClass(active.journal)">Food Journal</Link>
                        <Link :href="route('wellness.index')" :class="linkClass(active.wellness)">Wellness</Link>
                        <Link :href="route('products.index')" :class="linkClass(active.shop)">Shop</Link>
                    </nav>

                    <!-- Actions -->
                    <div class="ml-auto flex items-center gap-4 xl:gap-5">
                        <button
                            type="button"
                            @click="isSearchOpen = true"
                            class="flex h-11 w-11 items-center justify-center text-gray-800 transition-colors hover:text-primary"
                            aria-label="Search"
                        >
                            <PhMagnifyingGlass class="h-7 w-7" />
                        </button>

                        <span class="h-9 w-px bg-gray-500/50" aria-hidden="true"></span>

                        <!-- Account -->
                        <div class="group relative">
                            <Link
                                :href="user ? route('user.dashboard') : route('login')"
                                class="flex h-11 w-11 items-center justify-center text-gray-800 transition-colors hover:text-primary"
                                :aria-label="user ? 'My account' : 'Login or register'"
                            >
                                <PhUser class="h-7 w-7" />
                            </Link>

                            <div class="invisible absolute right-0 top-full z-50 w-56 pt-2 opacity-0 transition-all duration-200 group-hover:visible group-hover:opacity-100 group-focus-within:visible group-focus-within:opacity-100">
                                <div class="overflow-hidden rounded-2xl border border-gray-100 bg-white py-2 shadow-2xl">
                                    <template v-if="user">
                                        <div class="mb-1 border-b border-gray-50 bg-gray-50/50 px-4 py-3">
                                            <p class="mb-1 text-[10px] font-medium uppercase leading-none tracking-widest text-gray-400">Account</p>
                                            <p class="truncate text-sm font-medium text-gray-900">{{ user.name }}</p>
                                        </div>
                                        <Link
                                            :href="isAdmin ? route('admin.dashboard.index') : route('user.dashboard')"
                                            class="block px-4 py-2.5 text-sm font-medium text-gray-700 transition-colors hover:bg-gray-50 hover:text-primary"
                                        >
                                            {{ isAdmin ? "Admin Dashboard" : "My Dashboard" }}
                                        </Link>
                                        <Link :href="route('user.dashboard')" class="block px-4 py-2.5 text-sm font-medium text-gray-700 transition-colors hover:bg-gray-50 hover:text-primary">My Orders</Link>
                                        <div class="my-1 h-px bg-gray-50"></div>
                                        <Link :href="route('logout')" method="post" as="button" class="block w-full px-4 py-2.5 text-left text-sm font-medium text-red-500 transition-colors hover:bg-red-50">Logout</Link>
                                    </template>
                                    <template v-else>
                                        <Link :href="route('login')" class="block px-4 py-2.5 text-sm font-semibold text-gray-700 transition-colors hover:bg-gray-50 hover:text-primary">Login</Link>
                                        <Link :href="route('register')" class="block px-4 py-2.5 text-sm font-semibold text-gray-700 transition-colors hover:bg-gray-50 hover:text-primary">Register</Link>
                                    </template>
                                </div>
                            </div>
                        </div>

                        <button
                            type="button"
                            @click="toggleCartSidebar"
                            class="relative flex h-11 w-11 items-center justify-center text-gray-800 transition-colors hover:text-primary"
                            aria-label="Cart"
                        >
                            <PhHandbag class="h-7 w-7" />
                            <span
                                v-if="cartCount > 0"
                                class="absolute -right-0.5 top-0 flex h-5 min-w-[1.25rem] items-center justify-center rounded-full bg-primary px-1 text-[11px] font-semibold leading-none text-white"
                            >
                                {{ cartCount }}
                            </span>
                        </button>

                        <Link
                            :href="route('products.index')"
                            class="inline-flex h-14 items-center gap-2 rounded-full px-8 text-[17px] font-medium text-white shadow-sm transition-opacity hover:opacity-90"
                            style="background-color: color-mix(in srgb, var(--primary-color) 70%, black)"
                        >
                            Order Now
                            <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                <path d="M5 12h14M13 6l6 6-6 6" />
                            </svg>
                        </Link>
                    </div>
                </div>

                <!-- Mobile Layout (2 Rows) -->
                <div class="lg:hidden flex flex-col py-2">
                    <!-- Row 1: Menu, Logo, Cart -->
                    <div class="flex items-center justify-between mb-3">
                        <button
                            @click="emit('toggle-mobile-menu')"
                            class="p-1 -ml-1 text-gray-900 transition-transform active:scale-95"
                            aria-label="Menu"
                        >
                            <svg
                                xmlns="http://www.w3.org/2000/svg"
                                width="30"
                                height="16"
                                viewBox="0 0 30 16"
                                fill="currentColor"
                            >
                                <rect width="30" height="1.5"></rect>
                                <rect y="7" width="20" height="1.5"></rect>
                                <rect y="14" width="30" height="1.5"></rect>
                            </svg>
                        </button>

                        <Link
                            href="/"
                            class="flex-shrink-0 flex items-center gap-2"
                        >
                            <img
                                v-if="logoUrl"
                                :src="logoUrl"
                                :alt="siteName"
                                class="h-8 w-auto object-contain"
                            />
                            <span
                                v-else
                                class="text-lg font-black italic tracking-tighter text-gray-900"
                                >{{ siteName }}</span
                            >
                        </Link>

                        <button
                            @click="toggleCartSidebar"
                            class="relative p-1 -mr-1 text-gray-900 transition-transform active:scale-95"
                            aria-label="Cart"
                        >
                            <PhShoppingCartSimple class="w-7 h-7" />
                            <span
                                v-if="cartCount > 0"
                                class="absolute -top-1 -right-1 w-4 h-4 bg-primary text-white text-[9px] font-medium rounded-full flex items-center justify-center border-2 border-white"
                            >
                                {{ cartCount }}
                            </span>
                        </button>
                    </div>

                    <!-- Row 2: Full Width Search -->
                    <div
                        v-if="route().current() !== 'order.create'"
                        class="w-full"
                    >
                        <SearchBar
                            placeholder="Search products, models, brands..."
                        />
                    </div>
                </div>
            </div>
        </div>

        <!-- Search Overlay -->
        <transition
            enter-active-class="transition duration-300 ease-out"
            enter-from-class="opacity-0 translate-y-4"
            enter-to-class="opacity-100 translate-y-0"
            leave-active-class="transition duration-200 ease-in"
            leave-from-class="opacity-100 translate-y-0"
            leave-to-class="opacity-0 translate-y-4"
        >
            <div
                v-if="isSearchOpen"
                class="fixed inset-0 z-[60] bg-white pt-20 px-4"
            >
                <button
                    @click="isSearchOpen = false"
                    class="absolute top-6 right-6 p-2 bg-gray-50 rounded-full text-gray-500 hover:text-black transition-colors"
                    aria-label="Close search"
                >
                    <PhX class="w-6 h-6" />
                </button>
                <div class="max-w-xl mx-auto space-y-6">
                    <div class="text-center">
                        <h3 class="text-2xl font-medium text-gray-900">
                            What are you looking for?
                        </h3>
                        <p class="text-gray-500 mt-1">
                            Search through our wide range of products
                        </p>
                    </div>
                    <SearchBar
                        :auto-focus="true"
                        @close="isSearchOpen = false"
                    />
                </div>
            </div>
        </transition>

        <!-- Components -->
        <CartSidebar v-if="cartSidebarLoaded" :is-open="isCartSidebarOpen" @close="closeCartSidebar" />
        <MobileBottomNav
            v-if="!route().current('product.details')"
            :wishlist-count="wishlistCount"
        />
    </header>
</template>

