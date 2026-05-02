<script setup>
import { ref, computed } from "vue";
import { Link, usePage } from "@inertiajs/vue3";
import CategoryNav from "./CategoryNav.vue";
import CartSidebar from "./CartSidebar.vue";
import { useCartSidebar } from "@/Composables/useCartSidebar";
import SearchBar from "../Header/SearchBar.vue";
import MobileBottomNav from "./MobileBottomNav.vue";
import {
    MapPin,
    Search,
    ShoppingBag,
    User as UserIcon,
    LogIn,
    Menu,
    X,
    Heart,
} from "lucide-vue-next";

const props = defineProps({
    categories: {
        type: Array,
        default: () => [],
    },
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
const compareCount = computed(() => page.props.compareCount || 0);

const logoUrl = computed(() => {
    return props.settings?.media?.logo || "";
});

const siteName = computed(() => {
    return props.settings?.site_name || "E-Shop";
});

const formatPrice = (price) => {
    return new Intl.NumberFormat("en-BD", {
        minimumFractionDigits: 0,
        maximumFractionDigits: 0,
    }).format(price);
};

const cleantopnotice = (html) => {
    if (!html) return false;
    const text = html
        .replace(/<[^>]*>/g, "")
        .replace(/&nbsp;/g, "")
        .trim();
    return text.length > 0;
};
</script>

<template>
    <header class="sticky top-0 z-50 bg-white shadow-sm font-sans">
        <!-- Top Notice -->
        <div
            v-if="cleantopnotice(settings?.generalSettings?.top_notice)"
            class="hidden lg:block bg-[#0e1422] text-white py-2 overflow-hidden border-b border-white/5"
        >
            <div class="container mx-auto px-4 flex items-center justify-between">
                <div
                    class="text-[10px] sm:text-xs font-semibold uppercase tracking-widest text-gray-200"
                    v-html="settings?.generalSettings?.top_notice"
                ></div>
                
                <div class="flex items-center gap-6">
                    <Link
                        href="/wishlist"
                        class="flex items-center gap-1.5 text-[10px] sm:text-xs font-bold text-gray-300 hover:text-white transition-colors group relative"
                    >
                        <Heart class="w-3.5 h-3.5 group-hover:scale-110 transition-transform" />
                        <span>Wishlist</span>
                        <span
                            v-if="wishlistCount > 0"
                            class="absolute -top-2 -right-2 w-3.5 h-3.5 bg-primary text-white text-[8px] font-bold rounded-full flex items-center justify-center shadow-sm"
                        >
                            {{ wishlistCount }}
                        </span>
                    </Link>

                    <Link
                        href="/order/track"
                        class="flex items-center gap-1.5 text-[10px] sm:text-xs font-bold text-gray-300 hover:text-white transition-colors group"
                    >
                        <MapPin class="w-3.5 h-3.5 group-hover:scale-110 transition-transform" />
                        <span>Track Order</span>
                    </Link>
                </div>
            </div>
        </div>

        <!-- <pre>{{ settings }}</pre> -->

        <!-- Main Header -->
        <div class="bg-white border-b border-gray-50">
            <div class="container mx-auto px-4">
                <!-- Desktop Layout -->
                <div
                    class="hidden lg:flex items-center justify-between h-20 gap-12"
                >
                    <!-- Logo -->
                    <Link
                        href="/"
                        class="flex-shrink-0 flex items-center"
                    >
                        <img
                            v-if="logoUrl"
                            :src="logoUrl"
                            :alt="siteName"
                            class="h-10 w-auto object-contain"
                        />
                        <span
                            v-else
                            class="text-2xl font-bold tracking-tight text-gray-900"
                            >{{ siteName }}</span
                        >
                    </Link>

                    <!-- Search Bar (Centered) -->
                    <div class="flex-1 max-w-2xl px-4">
                        <SearchBar />
                    </div>

                    <!-- Actions -->
                    <div class="flex items-center gap-6">
                        <!-- Profile -->
                        <div class="relative group">
                            <Link
                                :href="route('user.dashboard')"
                                class="w-10 h-10 flex items-center justify-center text-gray-700 hover:text-primary transition-all rounded-full border border-gray-100 hover:bg-gray-50 bg-white shadow-sm"
                                title="Account"
                            >
                                <UserIcon class="w-5 h-5 stroke-[1.5]" />
                            </Link>

                            <!-- Dropdown -->
                            <div
                                class="absolute right-0 top-full mt-1 w-56 bg-white rounded-2xl shadow-2xl border border-gray-100 opacity-0 invisible group-hover:opacity-100 group-hover:visible transition-all duration-300 z-50 overflow-hidden py-2"
                            >
                                <template v-if="user">
                                    <div
                                        class="px-4 py-3 bg-gray-50/50 mb-1 border-b border-gray-50"
                                    >
                                        <p
                                            class="text-[10px] text-gray-400 font-bold uppercase tracking-widest leading-none mb-1"
                                        >
                                            Account
                                        </p>
                                        <p
                                            class="text-sm font-bold text-gray-900 truncate"
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
                                        class="block w-full text-left px-4 py-2.5 text-sm font-bold text-red-500 hover:bg-red-50 transition-colors"
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

                        <!-- Cart Button -->
                        <div class="relative">
                            <button
                                @click="toggleCartSidebar"
                                class="flex items-center gap-2.5 px-4 py-2.5 bg-[#0e1422] text-white rounded-full hover:bg-black transition-all shadow-md active:scale-95 group"
                            >
                                <div class="relative">
                                    <ShoppingBag class="w-5 h-5 stroke-[2]" />
                                    <span
                                        v-if="cartCount > 0"
                                        class="absolute -top-1.5 -right-1.5 w-4.5 h-4.5 bg-primary text-white text-[9px] font-bold rounded-full flex items-center justify-center border-2 border-[#0e1422]"
                                    >
                                        {{ cartCount }}
                                    </span>
                                </div>
                                <span class="text-sm font-bold"
                                    >৳{{
                                        formatPrice(page.props.cartTotal || 0)
                                    }}</span
                                >
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Mobile Layout (2 Rows) -->
                <div class="lg:hidden flex flex-col py-3">
                    <!-- Row 1: Menu, Logo, Cart -->
                    <div class="flex items-center justify-between mb-4">
                        <button
                            @click="emit('toggle-mobile-menu')"
                            class="p-1 -ml-1 text-gray-900 transition-transform active:scale-95"
                            aria-label="Menu"
                        >
                            <Menu class="w-8 h-8" />
                        </button>

                        <Link
                            href="/"
                            class="flex-shrink-0 flex items-center gap-2"
                        >
                            <img
                                v-if="logoUrl"
                                :src="logoUrl"
                                :alt="siteName"
                                class="h-10 w-auto object-contain"
                            />
                            <span
                                v-else
                                class="text-xl font-black italic tracking-tighter text-gray-900"
                                >{{ siteName }}</span
                            >
                        </Link>

                        <button
                            @click="toggleCartSidebar"
                            class="relative p-1 -mr-1 text-gray-900 transition-transform active:scale-95"
                            aria-label="Cart"
                        >
                            <ShoppingBag class="w-8 h-8" />
                            <span
                                v-if="cartCount > 0"
                                class="absolute -top-1.5 -right-1.5 w-5 h-5 bg-black text-white text-[10px] font-bold rounded-full flex items-center justify-center border-2 border-white"
                            >
                                {{ cartCount }}
                            </span>
                        </button>
                    </div>

                    <!-- Row 2: Full Width Search -->
                    <div class="w-full">
                        <SearchBar
                            placeholder="Search products, models, brands..."
                        />
                    </div>
                </div>
            </div>
        </div>

        <!-- Desktop Category Navigation -->
        <CategoryNav :categories="categories" class="hidden lg:block" />

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
                >
                    <X class="w-6 h-6" />
                </button>
                <div class="max-w-xl mx-auto space-y-6">
                    <div class="text-center">
                        <h3 class="text-2xl font-bold text-gray-900">
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
        <CartSidebar :is-open="isCartSidebarOpen" @close="closeCartSidebar" />
        <MobileBottomNav
            :wishlist-count="wishlistCount"
            @open-search="isSearchOpen = true"
        />
    </header>
</template>

<style scoped>
.container {
    max-width: 1400px;
}
</style>
