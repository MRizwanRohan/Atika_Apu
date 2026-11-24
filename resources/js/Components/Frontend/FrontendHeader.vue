<script setup>
import { ref, computed, watch, defineAsyncComponent } from "vue";
import { Link, usePage } from "@inertiajs/vue3";
const CartSidebar = defineAsyncComponent(() => import("./CartSidebar.vue"));
import { useCartSidebar } from "@/Composables/useCartSidebar";
import SearchBar from "../Header/SearchBar.vue";
import MobileBottomNav from "./MobileBottomNav.vue";
import {
    PhTruck,
    PhMagnifyingGlass,
    PhUser,
    PhX,
    PhGift,
    PhCube,
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

const user = computed(() => page.props.auth?.user);
const cartCount = computed(() => page.props.cartCount || 0);

const isAdmin = computed(() => {
    return user.value?.roles?.some((r) =>
        ["superadmin", "admin", "super-admin", "Admin", "Super Admin"].includes(
            r.name,
        ),
    );
});

const wishlistCount = computed(() => page.props.wishlistCount || 0);

const logoUrl = computed(() => {
    return props.settings?.media?.logo || "";
});

const siteName = computed(() => {
    return (
        props.settings?.generalSettings?.app_name ||
        props.settings?.site_name ||
        "Store"
    );
});

const cleantopnotice = (html) => {
    if (!html) return false;
    const text = html
        .replace(/<[^>]*>/g, "")
        .replace(/&nbsp;/g, "")
        .trim();
    return text.length > 0;
};

// Main menu. Keep the markup and classes in sync with resources/views/storefront-bootstrap.blade.php.
const navSauces = computed(() => page.props.navSauces || []);
const path = computed(() => (page.url || "/").split("?")[0]);
const active = computed(() => ({
    home: path.value === "/",
    shop: path.value.startsWith("/products") || path.value.startsWith("/product/"),
    sauces: path.value.startsWith("/category/sauce"),
    recipes: path.value.startsWith("/recipes"),
    about: path.value === "/page/about-us",
    contact: path.value === "/page/contact",
}));

const linkClass = (isActive) => [
    "relative inline-flex h-20 items-center gap-1 px-3 text-[15px] font-semibold transition-colors hover:text-primary",
    isActive
        ? "text-primary after:absolute after:inset-x-0 after:bottom-4 after:h-0.5 after:bg-primary"
        : "text-gray-900",
];
</script>

<template>
    <header class="sticky top-0 z-50 bg-white shadow-sm font-sans">
        <!-- Top Notice -->
        <div
            class="hidden lg:block bg-primary text-white py-1.5 overflow-hidden"
        >
            <div
                class="container mx-auto px-4 flex items-center justify-between"
            >
                <div
                    v-if="cleantopnotice(settings?.generalSettings?.top_notice)"
                    class="text-[10px] w-2/3 sm:text-xs tracking-widest text-white"
                    v-html="settings?.generalSettings?.top_notice"
                ></div>

                <div class="flex items-center justify-end w-full gap-6">
                    <Link
                        href="/order/track"
                        class="flex items-center gap-1.5 text-[10px] sm:text-xs font-medium text-white/90 hover:text-white transition-colors group"
                    >
                        <PhTruck
                            class="w-4 h-4 group-hover:scale-110 transition-transform"
                        />
                        <span>Track Order</span>
                    </Link>

                    <Link
                        :href="route('pre-order-lead.form')"
                        class="flex items-center gap-1.5 text-[10px] sm:text-xs font-medium text-white/90 hover:text-white transition-colors group"
                    >
                        <PhCube
                            class="w-4 h-4 group-hover:scale-110 transition-transform"
                        />
                        <span>Pre Order</span>
                    </Link>

                    <Link
                        href="/offers"
                        class="flex items-center gap-1.5 text-[10px] sm:text-xs font-medium text-white/90 hover:text-white transition-colors group"
                    >
                        <PhGift
                            class="w-4 h-4 group-hover:scale-110 transition-transform"
                        />
                        <span>Offers</span>
                    </Link>
                </div>
            </div>
        </div>

        <!-- Main Header -->
        <div class="bg-white border-b border-gray-100">
            <div class="container mx-auto px-4">
                <!-- Desktop Layout -->
                <div class="hidden lg:flex items-center justify-between h-20 gap-6">
                    <!-- Logo -->
                    <Link href="/" class="flex-shrink-0 flex items-center">
                        <img
                            v-if="logoUrl"
                            :src="logoUrl"
                            :alt="siteName"
                            class="h-14 w-auto max-w-[200px] object-contain"
                        />
                        <span
                            v-else
                            class="text-2xl font-black tracking-tighter text-gray-900"
                            >{{ siteName }}</span
                        >
                    </Link>

                    <!-- Menu -->
                    <nav aria-label="Main" class="flex items-center">
                        <Link href="/" :class="linkClass(active.home)">Home</Link>
                        <Link :href="route('products.index')" :class="linkClass(active.shop)">Shop</Link>

                        <div class="group relative">
                            <Link :href="route('category.show', 'sauce')" :class="linkClass(active.sauces)">
                                Sauces
                                <svg
                                    v-if="navSauces.length"
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
                                v-if="navSauces.length"
                                class="invisible absolute left-1/2 top-full z-50 w-72 -translate-x-1/2 opacity-0 transition duration-200 group-hover:visible group-hover:opacity-100 group-focus-within:visible group-focus-within:opacity-100"
                            >
                                <div class="rounded-2xl border border-gray-100 bg-white py-2 shadow-xl">
                                    <Link
                                        v-for="sauce in navSauces"
                                        :key="sauce.id"
                                        :href="route('product.details', sauce.slug)"
                                        class="flex items-center gap-3 px-4 py-2 transition-colors hover:bg-gray-50"
                                    >
                                        <img
                                            v-if="sauce.image"
                                            :src="sauce.image"
                                            :alt="sauce.name"
                                            loading="lazy"
                                            class="h-10 w-10 shrink-0 rounded-lg bg-gray-50 object-contain"
                                        />
                                        <span class="text-sm font-medium text-gray-800">{{ sauce.name }}</span>
                                    </Link>
                                    <Link
                                        :href="route('category.show', 'sauce')"
                                        class="mt-1 block border-t border-gray-100 px-4 pb-2 pt-3 text-sm font-semibold text-primary"
                                    >
                                        View all sauces →
                                    </Link>
                                </div>
                            </div>
                        </div>

                        <Link :href="route('recipes.index')" :class="linkClass(active.recipes)">Recipes</Link>
                        <Link :href="route('pages.show', 'about-us')" :class="linkClass(active.about)">About Us</Link>
                        <Link :href="route('pages.show', 'contact')" :class="linkClass(active.contact)">Contact</Link>
                    </nav>

                    <!-- Actions -->
                    <div class="flex items-center gap-1">
                        <button
                            type="button"
                            @click="isSearchOpen = true"
                            class="flex h-10 w-10 items-center justify-center text-gray-900 transition-colors hover:text-primary"
                            aria-label="Search"
                        >
                            <PhMagnifyingGlass class="h-6 w-6" />
                        </button>

                        <!-- Account -->
                        <div class="relative group">
                            <Link
                                :href="route('user.dashboard')"
                                class="flex h-10 w-10 items-center justify-center text-gray-900 transition-colors hover:text-primary"
                                :aria-label="user ? 'My account' : 'Login or register'"
                            >
                                <PhUser class="h-6 w-6" />
                            </Link>

                            <!-- Dropdown -->
                            <div
                                class="invisible absolute right-0 top-full z-50 w-56 pt-2 opacity-0 transition-all duration-300 group-hover:visible group-hover:opacity-100"
                            >
                                <div class="overflow-hidden rounded-2xl border border-gray-100 bg-white py-2 shadow-2xl">
                                    <template v-if="user">
                                        <div
                                            class="px-4 py-3 bg-gray-50/50 mb-1 border-b border-gray-50"
                                        >
                                            <p
                                                class="text-[10px] text-gray-400 font-medium uppercase tracking-widest leading-none mb-1"
                                            >
                                                Account
                                            </p>
                                            <p
                                                class="text-sm font-medium text-gray-900 truncate"
                                            >
                                                {{ user.name }}
                                            </p>
                                        </div>
                                        <Link
                                            :href="
                                                isAdmin
                                                    ? route('admin.dashboard.index')
                                                    : route('user.dashboard')
                                            "
                                            class="block px-4 py-2.5 text-sm font-medium text-gray-700 hover:bg-gray-50 hover:text-primary transition-colors"
                                        >
                                            {{
                                                isAdmin
                                                    ? "Admin Dashboard"
                                                    : "My Dashboard"
                                            }}
                                        </Link>
                                        <Link
                                            :href="route('user.dashboard')"
                                            class="block px-4 py-2.5 text-sm font-medium text-gray-700 hover:bg-gray-50 hover:text-primary transition-colors"
                                            >My Orders</Link
                                        >
                                        <div class="h-px bg-gray-50 my-1"></div>
                                        <Link
                                            :href="route('logout')"
                                            method="post"
                                            as="button"
                                            class="block w-full text-left px-4 py-2.5 text-sm font-medium text-red-500 hover:bg-red-50 transition-colors"
                                            >Logout</Link
                                        >
                                    </template>
                                    <template v-else>
                                        <Link
                                            :href="route('login')"
                                            class="block px-4 py-2.5 text-sm font-semibold text-gray-700 hover:bg-gray-50 hover:text-primary transition-colors"
                                            >Login</Link
                                        >
                                        <Link
                                            :href="route('register')"
                                            class="block px-4 py-2.5 text-sm font-semibold text-gray-700 hover:bg-gray-50 hover:text-primary transition-colors"
                                            >Register</Link
                                        >
                                    </template>
                                </div>
                            </div>
                        </div>

                        <!-- Cart Button -->
                        <button
                            type="button"
                            @click="toggleCartSidebar"
                            class="relative flex h-10 w-10 items-center justify-center text-gray-900 transition-colors hover:text-primary"
                            aria-label="Cart"
                        >
                            <PhShoppingCartSimple class="h-6 w-6" />
                            <span
                                class="absolute -right-0.5 -top-0.5 flex h-5 min-w-[1.25rem] items-center justify-center rounded-full bg-primary px-1 text-[11px] font-semibold leading-none text-white"
                            >
                                {{ cartCount }}
                            </span>
                        </button>
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

<style scoped>
.container {
    max-width: 1400px;
}
</style>
