<template>
    <header
        class="bg-white dark:bg-gray-900 border-b border-gray-200 dark:border-gray-800 h-20 flex items-center justify-between px-6 shadow-sm z-20 transition-colors duration-300"
    >
        <!-- Left Section: Toggle & Title -->
        <div class="flex items-center gap-4">
            <button
                @click="toggleSidebar"
                class="md:hidden p-2 rounded-lg text-gray-500 hover:bg-gray-100 dark:text-gray-400 dark:hover:bg-gray-800 dark:hover:text-white focus:outline-none transition-all duration-200"
            >
                <Menu class="h-6 w-6" />
            </button>

            <div class="hidden md:block">
                <h1
                    class="text-xl font-bold text-gray-800 dark:text-white tracking-tight"
                >
                    Admin Dashboard
                </h1>
                <p class="text-xs text-gray-500 dark:text-gray-400">
                    Welcome back, {{ $page.props.auth.user.name }}
                </p>
            </div>
        </div>

        <!-- Right Section: Actions -->
        <div class="flex items-center gap-2 sm:gap-4">
            <!-- Fullscreen Toggle -->
            <button
                @click="toggleFullscreen"
                class="p-2 rounded-lg text-gray-500 hover:bg-gray-100 dark:text-gray-400 dark:hover:bg-gray-800 dark:hover:text-white focus:outline-none transition-all duration-200"
                title="Toggle Fullscreen"
            >
                <Maximize2 v-if="!isFullscreen" class="h-5 w-5" />
                <Minimize2 v-else class="h-5 w-5" />
            </button>

            <!-- Dark Mode Toggle -->
            <button
                @click="toggleDarkMode"
                class="p-2 rounded-lg text-gray-500 hover:bg-gray-100 dark:text-gray-400 dark:hover:bg-gray-800 dark:hover:text-white focus:outline-none transition-all duration-200"
                title="Toggle Theme"
            >
                <Sun v-if="isDark" class="h-5 w-5" />
                <Moon v-else class="h-5 w-5" />
            </button>

            <!-- Notifications -->
            <div class="relative">
                <button
                    @click="toggleNotifications"
                    class="p-2 rounded-lg text-gray-500 hover:bg-gray-100 dark:text-gray-400 dark:hover:bg-gray-800 dark:hover:text-white focus:outline-none transition-all duration-200 relative"
                    title="Notifications"
                >
                    <Bell class="h-5 w-5" />
                    <span v-if="unreadCount > 0"
                        class="absolute top-0 right-0 inline-flex items-center justify-center px-1.5 py-0.5 text-[10px] font-semibold text-white bg-red-500 rounded-full border-2 border-white dark:border-gray-900"
                    >{{ unreadCount }}</span>
                </button>

                <transition
                    enter-active-class="transition ease-out duration-200"
                    enter-from-class="transform opacity-0 scale-95"
                    enter-to-class="transform opacity-100 scale-100"
                    leave-active-class="transition ease-in duration-75"
                    leave-from-class="transform opacity-100 scale-100"
                    leave-to-class="transform opacity-0 scale-95"
                >
                    <div
                        v-if="isNotificationsOpen"
                        class="fixed sm:absolute top-20 left-2 right-2 sm:right-0 sm:left-auto mt-0 sm:mt-3 w-auto sm:w-80 max-w-[calc(100vw-1rem)] sm:max-w-none bg-white dark:bg-gray-900 rounded-lg sm:rounded-xl shadow-xl py-2 z-50 border border-gray-100 dark:border-gray-800"
                    >
                        <div class="px-4 py-3 border-b border-gray-100 dark:border-gray-800">
                            <p class="text-sm font-semibold text-gray-900 dark:text-white text-center sm:text-left">Notifications</p>
                        </div>

                        <div class="px-2 py-2 max-h-[calc(100vh-6rem)] overflow-y-auto space-y-1">
                            <div v-if="notifications.length === 0" class="px-4 py-3 text-sm text-gray-500 dark:text-gray-400">
                                No notifications
                            </div>

                            <div v-for="n in notifications" :key="n.id" class="px-4 py-3 hover:bg-gray-50 dark:hover:bg-gray-800 rounded-md">
                                <div class="text-sm font-medium text-gray-800 dark:text-white truncate">
                                    {{ n.data.order_number ?? n.data.title ?? n.type }}
                                </div>
                                <div class="text-xs text-gray-500 dark:text-gray-400 truncate">
                                    <span v-if="n.data.customer_name">{{ n.data.customer_name }} • </span>
                                    <span v-if="n.data.total">{{ n.data.total }}</span>
                                </div>
                                <div class="text-xs text-gray-400 mt-1 truncate">{{ formatDate(n.created_at) }}</div>
                            </div>
                        </div>

                        <div class="border-t p-2 text-center">
                            <Link :href="route('admin.notifications.index')" class="text-sm text-blue-600 dark:text-blue-400">View all</Link>
                        </div>
                    </div>
                </transition>
            </div>

            <!-- Profile Dropdown -->
            <div class="relative ml-2">
                <button
                    @click="toggleProfile"
                    class="flex items-center gap-3 focus:outline-none group"
                >
                    <div
                        class="w-10 h-10 rounded-full bg-blue-100 dark:bg-blue-900/30 text-blue-600 dark:text-blue-400 flex items-center justify-center font-bold text-sm border-2 border-transparent group-hover:border-blue-200 dark:group-hover:border-blue-800 transition-all"
                    >
                        {{ $page.props.auth.user.name.charAt(0) }}
                    </div>
                    <div class="hidden md:block text-left">
                        <p
                            class="text-sm font-semibold text-gray-700 dark:text-gray-200 group-hover:text-blue-600 dark:group-hover:text-blue-400 transition-colors"
                        >
                            {{ $page.props.auth.user.name }}
                        </p>
                        <p class="text-xs text-gray-500 dark:text-gray-400">
                            Admin
                        </p>
                    </div>
                    <ChevronDown
                        class="h-4 w-4 text-gray-400 group-hover:text-gray-600 dark:group-hover:text-gray-300 transition-transform duration-200"
                        :class="{ 'rotate-180': isProfileOpen }"
                    />
                </button>

                <!-- Dropdown Menu -->
                <transition
                    enter-active-class="transition ease-out duration-200"
                    enter-from-class="transform opacity-0 scale-95"
                    enter-to-class="transform opacity-100 scale-100"
                    leave-active-class="transition ease-in duration-75"
                    leave-from-class="transform opacity-100 scale-100"
                    leave-to-class="transform opacity-0 scale-95"
                >
                    <div
                        v-if="isProfileOpen"
                        class="absolute right-0 mt-3 w-56 bg-white dark:bg-gray-900 rounded-xl shadow-xl py-2 z-50 border border-gray-100 dark:border-gray-800"
                    >
                        <div
                            class="px-4 py-3 border-b border-gray-100 dark:border-gray-800 mb-2"
                        >
                            <p
                                class="text-sm font-semibold text-gray-900 dark:text-white"
                            >
                                {{ $page.props.auth.user.name }}
                            </p>
                            <p
                                class="text-xs text-gray-500 dark:text-gray-400 truncate"
                            >
                                {{ $page.props.auth.user.email }}
                            </p>
                        </div>

                        <Link
                            class="flex items-center gap-2 px-4 py-2 text-sm text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-800 hover:text-blue-600 dark:hover:text-blue-400 transition-colors"
                            :href="route('admin.profile.edit')"
                        >
                            <User class="w-4 h-4" />
                            Profile Settings
                        </Link>

                        <div
                            class="border-t border-gray-100 dark:border-gray-800 my-2"
                        ></div>

                        <Link
                            class="flex items-center gap-2 w-full text-left px-4 py-2 text-sm text-red-600 hover:bg-red-50 dark:hover:bg-red-900/20 transition-colors"
                            :href="route('logout')"
                            method="post"
                            as="button"
                        >
                            <LogOut class="w-4 h-4" />
                            Log Out
                        </Link>
                    </div>
                </transition>
            </div>
        </div>
    </header>
</template>

<script setup>
import { defineProps, ref, computed } from "vue";
import { usePage } from "@inertiajs/vue3";
import {
    Menu,
    Sun,
    Moon,
    Maximize2,
    Minimize2,
    ChevronDown,
    Bell,
    User,
    LogOut,
} from "lucide-vue-next";
import { Link } from "@inertiajs/vue3";

defineProps({
    sidebarOpen: Boolean,
    toggleSidebar: Function,
    isDark: Boolean,
    toggleDarkMode: Function,
    isFullscreen: Boolean,
    toggleFullscreen: Function,
    isProfileOpen: Boolean,
    toggleProfile: Function,
});

// Notifications state
const isNotificationsOpen = ref(false);
const toggleNotifications = () => (isNotificationsOpen.value = !isNotificationsOpen.value);

const page = usePage();

// Safely resolve adminNotifications from Inertia props (handles SSR / shape differences)
const adminNotifications = computed(() => {
    try {
        // Prefer the .props.value shape (Vue 3 + Inertia), fall back to .props directly
        if (page && page.props && page.props.value && page.props.value.adminNotifications) {
            return page.props.value.adminNotifications;
        }
        if (page && page.props && page.props.adminNotifications) {
            return page.props.adminNotifications;
        }
    } catch (e) {
        // ignore and fall through
    }

    return { count: 0, notifications: [] };
});

const unreadCount = computed(() => adminNotifications.value?.count || 0);

// Normalize notifications into an array for safe iteration in template
const notifications = computed(() => {
    const n = adminNotifications.value?.notifications || [];
    if (Array.isArray(n)) return n;
    // Inertia pagination may provide an object with `data`
    if (n && n.data && Array.isArray(n.data)) return n.data;
    // As a last resort, convert object values to array
    if (n && typeof n === 'object') return Object.values(n);
    return [];
});

function formatDate(value) {
    if (!value) return '';
    try {
        return new Date(value).toLocaleString();
    } catch (e) {
        return value;
    }
}
</script>
