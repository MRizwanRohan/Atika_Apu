<script setup>
import { ref, computed } from "vue";
import { Link, usePage } from "@inertiajs/vue3";
import CategoryNav from "./CategoryNav.vue";
import CartSidebar from "./CartSidebar.vue";
import SearchBar from "../Header/SearchBar.vue";
import MobileBottomNav from "./MobileBottomNav.vue";
import {
    GitCompare,
    Gift,
    Heart,
    ShoppingCart,
    User as UserIcon,
    LogIn,
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
const searchQuery = ref("");
const isSearchFocused = ref(false);
const isCartSidebarOpen = ref(false);
const isMobileSearchOpen = ref(false);

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

const handleSearch = () => {
    if (searchQuery.value.trim()) {
        // Navigate to search page with query
        window.location.href = `/search?q=${encodeURIComponent(
            searchQuery.value,
        )}`;
    }
};

const toggleCartSidebar = () => {
    isCartSidebarOpen.value = !isCartSidebarOpen.value;
};

const closeCartSidebar = () => {
    isCartSidebarOpen.value = false;
};
</script>

<template>
    <header class="sticky top-0 z-50 bg-white shadow-sm">
        <!-- Search Overlay for Mobile -->
        <transition
            enter-active-class="transform transition duration-300 ease-out"
            enter-from-class="-translate-y-full opacity-0"
            enter-to-class="translate-y-0 opacity-100"
            leave-active-class="transform transition duration-200 ease-in"
            leave-from-class="translate-y-0 opacity-100"
            leave-to-class="-translate-y-full opacity-0"
        >
            <div
                v-if="isMobileSearchOpen"
                class="fixed inset-x-0 top-0 bg-white z-[70] p-4 shadow-lg lg:hidden border-b"
            >
                <div class="flex items-center gap-2">
                    <div class="flex-1">
                        <SearchBar
                            auto-focus
                            @close="isMobileSearchOpen = false"
                        />
                    </div>
                    <button
                        @click="isMobileSearchOpen = false"
                        class="p-2 hover:bg-gray-100 rounded-full text-gray-500 transition-colors"
                        aria-label="Close search"
                    >
                        <svg
                            xmlns="http://www.w3.org/2000/svg"
                            class="h-6 w-6"
                            fill="none"
                            viewBox="0 0 24 24"
                            stroke="currentColor"
                        >
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                stroke-width="2"
                                d="M6 18L18 6M6 6l12 12"
                            />
                        </svg>
                    </button>
                </div>
            </div>
        </transition>

        <!-- Main Header -->
        <div class="bg-[#1a1b21] text-white">
            <div class="container mx-auto px-4 py-3">
                <div class="flex items-center justify-between gap-4 lg:gap-8">
                    <!-- Mobile Menu Button -->
                    <button
                        @click="emit('toggle-mobile-menu')"
                        class="lg:hidden p-2 hover:bg-white/10 rounded-lg transition-colors text-white"
                    >
                        <svg
                            xmlns="http://www.w3.org/2000/svg"
                            class="h-6 w-6"
                            fill="none"
                            viewBox="0 0 24 24"
                            stroke="currentColor"
                        >
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                stroke-width="2"
                                d="M4 6h16M4 12h16M4 18h16"
                            />
                        </svg>
                    </button>

                    <Link
                        v-if="logoUrl"
                        href="/"
                        class="flex items-center gap-2 shrink-0"
                    >
                        <img
                            :src="logoUrl"
                            :alt="siteName"
                            class="h-9 md:h-12 w-auto object-contain"
                        />
                    </Link>

                    <!-- Search Bar (Desktop) -->
                    <div class="hidden lg:block flex-1 max-w-2xl px-4">
                        <SearchBar variant="dark" />
                    </div>

                    <!-- Right Actions -->
                    <div class="flex items-center gap-4 xl:gap-8">
                        <!-- Navigation Links (Desktop) -->
                        <div
                            class="hidden xl:flex items-center gap-6 text-sm font-medium"
                        >
                            <Link
                                :href="route('pre-order-lead.form')"
                                class="hover:text-primary transition-colors"
                                >Pre-order</Link
                            >
                            <Link
                                :href="route('offers.index')"
                                class="flex items-center gap-1.5 hover:text-primary transition-colors text-primary font-bold"
                            >
                                <Gift class="w-4 h-4" />
                                <span>Offers</span>
                            </Link>
                            <Link
                                href="/compare"
                                class="relative flex items-center gap-1.5 hover:text-primary transition-colors"
                            >
                                <GitCompare class="w-4 h-4" />
                                <span>Compare</span>
                                <span
                                    v-if="compareCount > 0"
                                    class="absolute -top-1.5 -right-2 w-4 h-4 bg-primary text-white text-[10px] font-bold rounded-full flex items-center justify-center"
                                >
                                    {{
                                        compareCount > 99 ? "99+" : compareCount
                                    }}
                                </span>
                            </Link>
                        </div>

                        <div class="flex items-center gap-3 md:gap-4">
                            <!-- Wishlist -->
                            <Link
                                href="/wishlist"
                                class="relative p-2.5 hover:bg-white/10 rounded-full transition-colors hidden lg:block border border-white/20"
                            >
                                <Heart class="w-5 h-5" />
                                <span
                                    v-if="wishlistCount > 0"
                                    class="absolute -top-1 -right-1 w-4 h-4 bg-primary text-white text-[10px] font-bold rounded-full flex items-center justify-center"
                                >
                                    {{
                                        wishlistCount > 99
                                            ? "99+"
                                            : wishlistCount
                                    }}
                                </span>
                            </Link>

                            <!-- Cart -->
                            <button
                                @click="toggleCartSidebar"
                                class="relative p-2.5 hover:bg-white/10 rounded-full transition-colors border border-white/20"
                            >
                                <ShoppingCart class="w-5 h-5" />
                                <span
                                    v-if="cartCount > 0"
                                    class="absolute -top-1 -right-1 w-4 h-4 bg-primary text-white text-[10px] font-bold rounded-full flex items-center justify-center"
                                >
                                    {{ cartCount > 99 ? "99+" : cartCount }}
                                </span>
                            </button>

                            <!-- User Menu -->
                            <div class="relative group">
                                <Link
                                    :href="route('user.dashboard')"
                                    class="p-2.5 inline-flex items-center hover:bg-white/10 rounded-full transition-colors border border-white/20"
                                >
                                    <UserIcon class="w-5 h-5" />
                                </Link>

                                <!-- Dropdown Menu -->
                                <div
                                    class="absolute right-0 top-full mt-2 w-48 bg-white rounded-lg shadow-xl border border-gray-100 opacity-0 invisible group-hover:opacity-100 group-hover:visible transition-all duration-200 z-50 overflow-hidden"
                                >
                                    <div class="py-1">
                                        <template v-if="user">
                                            <div
                                                class="px-4 py-3 bg-gray-50 border-b"
                                            >
                                                <p
                                                    class="text-xs text-gray-500 font-medium"
                                                >
                                                    Signed in as
                                                </p>
                                                <p
                                                    class="text-sm font-bold text-gray-900 truncate"
                                                >
                                                    {{ user.name }}
                                                </p>
                                            </div>
                                            <Link
                                                v-if="isAdmin"
                                                :href="
                                                    route(
                                                        'admin.dashboard.index',
                                                    )
                                                "
                                                class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100 font-medium transition-colors"
                                                >Admin Dashboard</Link
                                            >
                                            <Link
                                                v-else
                                                :href="route('user.dashboard')"
                                                class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100 font-medium transition-colors"
                                                >My Dashboard</Link
                                            >
                                            <Link
                                                :href="route('user.dashboard')"
                                                class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100 transition-colors"
                                                >My Orders</Link
                                            >
                                            <Link
                                                href="/wishlist"
                                                class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100 transition-colors"
                                                >Wishlist</Link
                                            >
                                            <div class="border-t my-1"></div>
                                            <Link
                                                :href="route('logout')"
                                                method="post"
                                                as="button"
                                                class="block w-full text-left px-4 py-2 text-sm text-red-600 hover:bg-red-50 font-medium transition-colors"
                                                >Logout</Link
                                            >
                                        </template>
                                        <template v-else>
                                            <Link
                                                :href="route('login')"
                                                class="px-4 py-2 text-sm text-gray-700 hover:bg-gray-100 font-medium transition-colors flex items-center gap-2"
                                            >
                                                <LogIn class="w-4 h-4" />
                                                Login
                                            </Link>
                                            <Link
                                                :href="route('register')"
                                                class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100 font-medium transition-colors"
                                                >Register</Link
                                            >
                                        </template>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Category Navigation -->
        <CategoryNav :categories="categories" />

        <!-- Mobile Bottom Navigation -->
        <MobileBottomNav
            :wishlist-count="wishlistCount"
            :compare-count="compareCount"
            @open-search="isMobileSearchOpen = true"
        />

        <!-- Cart Sidebar -->
        <CartSidebar :is-open="isCartSidebarOpen" @close="closeCartSidebar" />
    </header>
</template>
