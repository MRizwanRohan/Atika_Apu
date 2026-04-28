<template>
    <div
        class="sidebar bg-white dark:bg-gray-900 border-r border-gray-200 dark:border-gray-800 transition-all duration-300 ease-in-out h-full shadow-xl flex flex-col"
        :class="{ 'w-20': !sidebarOpen, 'w-52': sidebarOpen }"
    >
        <!-- Logo Section -->
        <div
            class="flex items-center justify-center border-b border-gray-100 dark:border-gray-800 bg-white dark:bg-gray-900 z-10 flex-shrink-0"
        >
            <div
                class="flex items-center justify-center w-full overflow-hidden"
            >
                <img
                    v-if="sidebarOpen"
                    src="/assets/img/logo/logo.png"
                    alt="Logo"
                    class="w-[100px] py-3 object-contain transition-all duration-300 animate-in fade-in zoom-in-95"
                />
                <img
                    v-else
                    src="/assets/img/logo/favicon.png"
                    alt="Logo Icon"
                    class="h-10 w-10 object-contain transition-all duration-300 animate-in fade-in zoom-in-95"
                />
            </div>
        </div>

        <!-- Navigation Section -->
        <div class="flex-1 overflow-y-auto py-4 px-2 scrollbar-hide">
            <ul class="space-y-1.5">
                <template v-for="(item, index) in navigationItems" :key="index">
                    <!-- Single Link -->
                    <li v-if="shouldShowItem(item)" class="relative group">
                        <template v-if="!item.children">
                            <Link
                                :href="route(item.route)"
                                class="flex items-center px-2 py-2 rounded-xl transition-all duration-200 group relative overflow-hidden"
                                :class="[
                                    isActive(item.route)
                                        ? 'bg-blue-600 text-white shadow-md shadow-blue-500/30'
                                        : 'text-gray-600 dark:text-gray-400 hover:bg-gray-50 dark:hover:bg-gray-800 hover:text-blue-600 dark:hover:text-blue-400',
                                    !sidebarOpen ? 'justify-center' : '',
                                ]"
                            >
                                <component
                                    :is="item.icon"
                                    class="w-6 h-6 flex-shrink-0 transition-transform duration-200 group-hover:scale-110"
                                    :class="{ 'mr-3': sidebarOpen }"
                                />

                                <span
                                    v-if="sidebarOpen"
                                    class="font-medium text-[12px] whitespace-nowrap transition-all duration-300"
                                >
                                    {{ item.label }}
                                    <span
                                        v-if="item.badge"
                                        class="ml-2 px-1.5 py-0.5 text-[10px] font-bold bg-yellow-400 text-black rounded-md"
                                        >{{ item.badge }}</span
                                    >
                                </span>

                                <!-- Tooltip for collapsed state -->
                                <div
                                    v-if="!sidebarOpen"
                                    class="absolute left-full top-1/2 -translate-y-1/2 ml-4 px-3 py-2 bg-gray-900 text-white text-xs rounded-md opacity-0 group-hover:opacity-100 pointer-events-none transition-opacity duration-200 whitespace-nowrap z-50 shadow-xl"
                                >
                                    {{ item.label }}
                                    <div
                                        class="absolute left-0 top-1/2 -translate-y-1/2 -ml-1 w-2 h-2 bg-gray-900 transform rotate-45"
                                    ></div>
                                </div>
                            </Link>
                        </template>

                        <!-- Dropdown Menu -->
                        <template v-else>
                            <button
                                @click="toggleSubmenu(item.id)"
                                class="w-full flex items-center px-3 py-3 rounded-xl transition-all duration-200 group relative"
                                :class="[
                                    isSubmenuActive(item)
                                        ? 'bg-blue-50 dark:bg-gray-800/50 text-blue-600 dark:text-blue-400'
                                        : 'text-gray-600 dark:text-gray-400 hover:bg-gray-50 dark:hover:bg-gray-800 hover:text-blue-600 dark:hover:text-blue-400',
                                    !sidebarOpen ? 'justify-center' : '',
                                ]"
                            >
                                <component
                                    :is="item.icon"
                                    class="w-6 h-6 flex-shrink-0 transition-transform duration-200 group-hover:scale-110"
                                    :class="{ 'mr-3': sidebarOpen }"
                                />

                                <span
                                    v-if="sidebarOpen"
                                    class="flex-1 text-left font-medium text-[12px] whitespace-nowrap"
                                >
                                    {{ item.label }}
                                </span>

                                <ChevronDown
                                    v-if="sidebarOpen"
                                    class="w-4 h-4 transition-transform duration-300"
                                    :class="{
                                        'rotate-180': openSubmenus[item.id],
                                    }"
                                />

                                <!-- Tooltip for collapsed state -->
                                <div
                                    v-if="!sidebarOpen"
                                    class="absolute left-full top-1/2 -translate-y-1/2 ml-4 px-3 py-2 bg-gray-900 text-white text-xs rounded-md opacity-0 group-hover:opacity-100 pointer-events-none transition-opacity duration-200 whitespace-nowrap z-50 shadow-xl"
                                >
                                    {{ item.label }}
                                    <div
                                        class="absolute left-0 top-1/2 -translate-y-1/2 -ml-1 w-2 h-2 bg-gray-900 transform rotate-45"
                                    ></div>
                                </div>
                            </button>

                            <!-- Submenu Items -->
                            <div
                                v-if="sidebarOpen"
                                class="overflow-hidden transition-all duration-300 ease-in-out"
                                :style="{
                                    maxHeight: openSubmenus[item.id]
                                        ? '1000px'
                                        : '0px',
                                }"
                            >
                                <ul class="mt-1 space-y-1 pl-10 pr-2 relative">
                                    <!-- Vertical line for hierarchy -->
                                    <div
                                        class="absolute left-[22px] top-0 bottom-0 w-px bg-gray-200 dark:bg-gray-700"
                                    ></div>

                                    <li
                                        v-for="(
                                            child, childIndex
                                        ) in getVisibleChildren(item)"
                                        :key="childIndex"
                                    >
                                        <Link
                                            :href="route(child.route)"
                                            class="flex items-center py-2 px-3 rounded-lg text-[12px] transition-colors duration-200 relative"
                                            :class="[
                                                isActive(child.route)
                                                    ? 'text-blue-600 dark:text-blue-400 font-semibold bg-blue-50 dark:bg-blue-900/20'
                                                    : 'text-gray-500 dark:text-gray-400 hover:text-blue-600 dark:hover:text-blue-400 hover:bg-gray-50 dark:hover:bg-gray-800',
                                            ]"
                                        >
                                            <!-- Dot for hierarchy -->
                                            <span
                                                class="absolute -left-[18px] top-1/2 -translate-y-1/2 w-1.5 h-1.5 rounded-full transition-colors duration-200"
                                                :class="
                                                    isActive(child.route)
                                                        ? 'bg-blue-600'
                                                        : 'bg-gray-300 dark:bg-gray-600'
                                                "
                                            ></span>
                                            {{ child.label }}
                                        </Link>
                                    </li>
                                </ul>
                            </div>
                        </template>
                    </li>
                </template>
            </ul>
        </div>

        <!-- Footer / User Info (Optional) -->
        <div
            class="p-4 border-t border-gray-100 dark:border-gray-800 bg-white dark:bg-gray-900"
        >
            <div
                class="flex items-center"
                :class="{ 'justify-center': !sidebarOpen }"
            >
                <div
                    class="w-8 h-8 rounded-full bg-blue-100 text-blue-600 flex items-center justify-center font-bold text-xs"
                >
                    {{ $page.props.auth.user.name.charAt(0) }}
                </div>
                <div v-if="sidebarOpen" class="ml-3 overflow-hidden">
                    <p
                        class="text-sm font-medium text-gray-900 dark:text-white truncate"
                    >
                        {{ $page.props.auth.user.name }}
                    </p>
                    <p class="text-xs text-gray-500 truncate">
                        {{ $page.props.auth.user.email }}
                    </p>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup>
import { defineProps, computed, watch } from "vue";
import {
    ChevronDown,
    Home,
    Users,
    ShoppingCart,
    Settings,
    UserRoundPlus,
    BadgeDollarSign,
    TicketPercent,
    Newspaper,
    FilePenLine,
    FileText,
    Megaphone,
    Truck,
    LayoutDashboard,
    Wallet,
    ShieldCheck,
    Globe,
} from "lucide-vue-next";
import { Link, usePage } from "@inertiajs/vue3";

const page = usePage();

const props = defineProps({
    sidebarOpen: {
        type: Boolean,
        default: true,
    },
    openSubmenus: {
        type: Object,
        default: () => ({}),
    },
    toggleSubmenu: {
        type: Function,
        required: true,
    },
});

// User Permissions Logic
const userPermissions = computed(
    () => page.props.auth?.user?.permissions || [],
);
const userRoles = computed(() => page.props.auth?.user?.roles || []);
const userIsAdmin = computed(
    () =>
        !!page.props.auth?.user?.is_admin ||
        userRoles.value.includes("admin") ||
        userRoles.value.includes("super-admin"),
);

const hasPermission = (permission) => {
    if (userIsAdmin.value) return true;
    return Array.isArray(userPermissions.value)
        ? userPermissions.value.includes(permission)
        : false;
};

const hasAnyPermission = (permissions) => {
    if (userIsAdmin.value) return true;
    if (!Array.isArray(permissions)) return false;
    return permissions.some((p) => hasPermission(p));
};

// Navigation Data
const navigationItems = [
    {
        label: "Dashboard",
        route: "admin.dashboard.index",
        icon: LayoutDashboard,
        permission: "admin.dashboard.index",
        badge: userIsAdmin.value ? "Admin" : null,
    },
    {
        label: "Orders",
        route: "admin.orders.index",
        icon: ShoppingCart,
        permission: "admin.orders.index",
    },
    {
        label: "Incomplete Orders",
        route: "admin.orders.incomplete",
        icon: ShoppingCart,
        permission: "admin.orders.index",
    },
    // {
    //     label: "Pre-Order Leads",
    //     route: "admin.leads.index",
    //     icon: UserRoundPlus,
    //     permission: "admin.leads.index",
    // },
    {
        label: "POS",
        route: "admin.pos.index",
        icon: Newspaper,
        permission: "admin.pos.index",
    },
    {
        label: "Products",
        icon: Globe,
        id: 1,
        children: [
            {
                label: "All Products",
                route: "admin.products.index",
                permission: "admin.products.index",
            },
            {
                label: "Add Product",
                route: "admin.products.create",
                permission: "admin.products.create",
            },
            {
                label: "Attributes",
                route: "admin.attributes.index",
                permission: "admin.attributes.index",
            },
            {
                label: "Categories",
                route: "admin.categories.index",
                permission: "admin.categories.index",
            },
            {
                label: "Brands",
                route: "admin.brands.index",
                permission: "admin.brands.index",
            },
            {
                label: "Product Groups",
                route: "admin.product-groups.index",
                permission: "admin.product-groups.index",
            },
            {
                label: "Product Videos",
                route: "admin.product-videos.index",
                permission: "admin.product-videos.index",
            },
        ],
    },
    {
        label: "Inventory",
        icon: Truck,
        id: 2,
        children: [
            {
                label: "Stocks",
                route: "admin.inventory.getAllProductsStock",
                permission: "admin.inventory.getAllProductsStock",
            },
            {
                label: "Report",
                route: "admin.reports.generateReport",
                permission: "admin.reports.generateReport",
            },
        ],
    },
    {
        label: "Employees",
        icon: UserRoundPlus,
        id: 5,
        children: [
            {
                label: "All Employees",
                route: "admin.employee.index",
                permission: "admin.employee.index",
            },
            {
                label: "Employee Salaries",
                route: "admin.employee.salary.index",
                permission: "admin.employee.salary.index",
            },
            {
                label: "Team",
                route: "admin.team-member.index",
                permission: "admin.team-member.index",
            },
        ],
    },
    // {
    //     label: "Transactions",
    //     route: "admin.transaction-history.index",
    //     icon: Wallet,
    //     permission: "admin.transaction-history.index",
    // },
    {
        label: "Accounting",
        icon: BadgeDollarSign,
        id: 6,
        children: [
            {
                label: "Business Dashboard",
                route: "admin.business-dashboard.index",
                permission: "admin.business-dashboard.index",
            },
            {
                label: "Suppliers",
                route: "admin.suppliers.index",
                permission: "admin.suppliers.index",
            },
            {
                label: "Purchases",
                route: "admin.purchases.index",
                permission: "admin.purchases.index",
            },
            // {
            //     label: "Expenses",
            //     route: "admin.expenses.index",
            //     permission: "admin.expenses.index",
            // },
            {
                label: "Expense Categories",
                route: "admin.expense-categories.index",
                permission: "admin.expense-categories.index",
            },
            {
                label: "Expenses",
                route: "admin.expenses.index",
                permission: "admin.expenses.index",
            },
            // {
            //     label: "Purchase Costs (Legacy)",
            //     route: "admin.product-purchase-costs.index",
            //     permission: "admin.product-purchase-costs.index",
            // },
        ],
    },
    {
        label: "Users & Access",
        icon: ShieldCheck,
        id: 7,
        children: [
            {
                label: "Users",
                route: "admin.users.index",
                permission: "admin.users.index",
            },
            {
                label: "Roles & Permissions",
                route: "admin.roles.index",
                permission: "admin.roles.index",
            },
        ],
    },
    {
        label: "Coupons",
        route: "admin.coupons.index",
        icon: TicketPercent,
        permission: "admin.coupons.index",
    },
    {
        label: "Campaigns",
        route: "admin.campaigns.index",
        icon: Megaphone,
        permission: "admin.campaigns.index",
    },
    // {
    //     label: "Corporate Clients",
    //     route: "admin.corporate-clients.index",
    //     icon: Users,
    //     permission: "admin.corporate-clients.index",
    // },
    {
        label: "Landing Pages",
        route: "admin.landing-pages.index",
        icon: FilePenLine,
        permission: "admin.landing-pages.index",
    },
    {
        label: "Site Pages",
        route: "admin.site-pages.index",
        icon: FilePenLine,
        permission: "admin.site-pages.index",
    },
    // {
    //     label: "Blogs",
    //     route: "admin.blogs.index",
    //     icon: FileText,
    //     permission: "admin.blogs.index",
    // },
    {
        label: "API Settings",
        icon: Settings,
        id: 3,
        children: [
            {
                label: "Courier API",
                route: "admin.courier.settings.index",
                permission: "admin.courier.settings.index",
            },
        ],
    },
    {
        label: "Reviews",
        route: "admin.reviews.index",
        icon: FilePenLine,
        permission: "admin.reviews.index",
    },
    {
        label: "Settings",
        icon: Settings,
        id: 4,
        children: [
            {
                label: "Basic",
                route: "admin.settings.basicInformation",
                permission: "admin.settings.basicInformation",
            },
            {
                label: "Media",
                route: "admin.media.index",
                permission: "admin.media.index",
            },
            {
                label: "Sliders",
                route: "admin.sliders.index",
                permission: "admin.sliders.index",
            },
            {
                label: "Banners",
                route: "admin.banners.index",
                permission: "admin.banners.index",
            },
            {
                label: "Instagram Feed",
                route: "admin.instagram-feeds.index",
                permission: "admin.instagram-feeds.index",
            },
            // { label: "Promotional Sliders", route: "admin.promotional-sliders.index", permission: "admin.promotional-sliders.index" },
            {
                label: "Social Links",
                route: "admin.settings.socialLinks",
                permission: "admin.settings.socialLinks",
            },
            {
                label: "Marketing Tools",
                route: "admin.marketing-tools.index",
                permission: "admin.marketing-tools.index",
            },
            {
                label: "Meta Tracking",
                route: "admin.meta-tracking.settings",
                permission: "admin.meta-tracking.settings",
            },
        ],
    },
];

// Helper Functions
const shouldShowItem = (item) => {
    if (userIsAdmin.value) return true;
    if (item.children) {
        // Show parent menu if user has permission for at least one child
        return item.children.some((child) => hasPermission(child.permission));
    }
    return hasPermission(item.permission);
};

// Check if a child menu item should be shown
const shouldShowChild = (child) => {
    if (userIsAdmin.value) return true;
    return hasPermission(child.permission);
};

// Get visible children for a menu item
const getVisibleChildren = (item) => {
    if (userIsAdmin.value) return item.children;
    return item.children.filter((child) => hasPermission(child.permission));
};

const isActive = (routeName) => {
    return route().current(routeName);
};

const isSubmenuActive = (item) => {
    if (!item.children) return false;
    return item.children.some((child) => isActive(child.route));
};

// Watchers
watch(
    () => route().current(),
    (currentRoute) => {
        navigationItems.forEach((item) => {
            if (item.children) {
                const hasActiveChild = item.children.some((child) =>
                    route().current(child.route),
                );
                if (hasActiveChild) {
                    props.toggleSubmenu(item.id, true);
                }
            }
        });
    },
    { immediate: true },
);
</script>

<style scoped>
.scrollbar-hide::-webkit-scrollbar {
    display: none;
}
.scrollbar-hide {
    -ms-overflow-style: none;
    scrollbar-width: none;
}
</style>
