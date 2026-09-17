<script setup>
import { ref, computed } from "vue";
import { Link, usePage } from "@inertiajs/vue3";
import CategoryNav from "./CategoryNav.vue";
import CartSidebar from "./CartSidebar.vue";
import { useCartSidebar } from "@/Composables/useCartSidebar";
import SearchBar from "../Header/SearchBar.vue";
import MobileBottomNav from "./MobileBottomNav.vue";
import {
    PhTruck,
    PhMagnifyingGlass,
    PhShoppingBag,
    PhUser,
    PhSignOut,
    PhList,
    PhX,
    PhHeart,
    PhGift,
    PhCube,
    PhShoppingCartSimple,
} from "@phosphor-icons/vue";

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

        <!-- <pre>{{ settings }}</pre> -->

        <!-- Main Header -->
        <div class="bg-white border-b border-gray-100">
            <div class="container mx-auto px-4">
                <!-- Desktop Layout -->
                <div
                    class="hidden lg:flex items-center justify-between h-14 gap-12"
                >
                    <!-- Logo -->
                    <Link
                        href="/"
                        class="flex-shrink-0 flex items-center group"
                    >
                        <img
                            v-if="logoUrl"
                            :src="logoUrl"
                            :alt="siteName"
                            class="w-[130px] object-contain"
                        />
                        <div
                            v-else
                            class="text-2xl font-black tracking-tighter"
                        >
                            <span class="text-gray-900">Smart</span
                            ><span class="text-primary">Gear</span>
                        </div>
                    </Link>

                    <!-- Search Bar (Centered) -->
                    <div
                        v-if="route().current() !== 'order.create'"
                        class="flex-1 max-w-2xl px-4"
                    >
                        <SearchBar />
                    </div>

                    <!-- Actions -->
                    <div class="flex items-center gap-6">
                        <!-- Account -->
                        <div class="relative group">
                            <Link
                                :href="route('user.dashboard')"
                                class="flex flex-col items-center gap-0.5 text-gray-700 hover:text-primary transition-colors group"
                            >
                                <div
                                    class="w-8 h-8 flex items-center justify-center rounded-lg bg-gray-50 group-hover:bg-primary/5 transition-all"
                                >
                                    <PhUser class="w-5 h-5" />
                                </div>
                                <span
                                    class="text-[10px] font-medium uppercase tracking-tight"
                                >
                                    {{
                                        user
                                            ? user.name.split(" ")[0]
                                            : "Login/Register"
                                    }}
                                </span>
                            </Link>

                            <!-- Dropdown -->
                            <div
                                class="absolute right-0 top-full mt-2 w-56 bg-white rounded-2xl shadow-2xl border border-gray-100 opacity-0 invisible group-hover:opacity-100 group-hover:visible transition-all duration-300 z-50 overflow-hidden py-2"
                            >
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

                        <!-- Wishlist -->
                        <!-- <Link
                            href="/wishlist"
                            class="flex flex-col items-center gap-1 text-gray-700 hover:text-primary transition-colors group"
                        >
                            <div class="relative w-10 h-10 flex items-center justify-center rounded-xl bg-gray-50 group-hover:bg-primary/5 transition-all">
                                <Heart class="w-6 h-6 stroke-[1.5]" />
                                <span
                                    v-if="wishlistCount > 0"
                                    class="absolute -top-1 -right-1 w-5 h-5 bg-primary text-white text-[10px] font-medium rounded-full flex items-center justify-center border-2 border-white"
                                >
                                    {{ wishlistCount }}
                                </span>
                            </div>
                            <span class="text-[11px] font-medium uppercase tracking-tight">Wishlist</span>
                        </Link> -->

                        <!-- Cart Button -->
                        <button
                            @click="toggleCartSidebar"
                            class="flex flex-col items-center gap-0.5 text-gray-700 hover:text-primary transition-colors group"
                        >
                            <div
                                class="relative w-8 h-8 flex items-center justify-center rounded-lg bg-gray-50 group-hover:bg-primary/5 transition-all"
                            >
                                <PhShoppingCartSimple class="w-5 h-5" />
                                <span
                                    class="absolute -top-1 -right-1 w-4 h-4 bg-primary text-white text-[9px] font-medium rounded-full flex items-center justify-center border-2 border-white"
                                >
                                    {{ cartCount }}
                                </span>
                            </div>
                            <span
                                class="text-[10px] font-medium uppercase tracking-tight"
                                >Cart</span
                            >
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
                                data-v-c6900758=""
                                data-v-68fa2a2f=""
                                xmlns="http://www.w3.org/2000/svg"
                                width="30"
                                height="16"
                                viewBox="0 0 30 16"
                                fill="currentColor"
                            >
                                <rect
                                    data-v-c6900758=""
                                    data-v-68fa2a2f=""
                                    width="30"
                                    height="1.5"
                                ></rect>
                                <rect
                                    data-v-c6900758=""
                                    data-v-68fa2a2f=""
                                    y="7"
                                    width="20"
                                    height="1.5"
                                ></rect>
                                <rect
                                    data-v-c6900758=""
                                    data-v-68fa2a2f=""
                                    y="14"
                                    width="30"
                                    height="1.5"
                                ></rect>
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
                                class="absolute -top-1 -right-1 w-4 h-4 bg-black text-white text-[9px] font-medium rounded-full flex items-center justify-center border-2 border-white"
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
        <CartSidebar :is-open="isCartSidebarOpen" @close="closeCartSidebar" />
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
