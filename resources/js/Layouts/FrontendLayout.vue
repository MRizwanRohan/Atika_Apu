<script setup>
import "../../css/frontend.css";
import { ref, computed, onMounted, onUnmounted, watch } from "vue";
import { usePage, Head } from "@inertiajs/vue3";
import FrontendHeader from "@/Components/Frontend/FrontendHeader.vue";
import FrontendFooter from "@/Components/Frontend/FrontendFooter.vue";
import MobileMenu from "@/Components/Frontend/MobileMenu.vue";
import Toast from "@/Components/Frontend/Toast.vue";
import { Toaster } from "@steveyuowo/vue-hot-toast";
import { useToast } from "@/Composables/useToast";

const page = usePage();
const isMobileMenuOpen = ref(false);

const categories = computed(() => page.props.categories || []);
const settings = computed(() => page.props.settings || {});
const pages = computed(() => page.props.pages || []);
const media = computed(() => settings.value.media || {});
const marketingTools = computed(() => settings.value.marketingTools || []);
const generalSettings = computed(() => settings.value.generalSettings || {});

const setPrimaryColor = () => {
    const color = generalSettings.value?.primary_color || "#f0512e";
    document.documentElement.style.setProperty("--primary-color", color);
};

watch(generalSettings, () => {
    setPrimaryColor();
});

const toggleMobileMenu = () => {
    isMobileMenuOpen.value = !isMobileMenuOpen.value;
};

const closeMobileMenu = () => {
    isMobileMenuOpen.value = false;
};

onMounted(() => {
    setPrimaryColor();

    // Initial injection of marketing tools
    injectMarketingTools();

    // Add frontend class to body so frontend-only font applies
    document.body.classList.add("frontend");
});

onUnmounted(() => {
    // Remove frontend class when layout is destroyed
    document.body.classList.remove("frontend");
});

// Handle Toast Notifications from Flash Messages
const {
    success: showSuccess,
    error: showError,
    warning: showWarning,
    info: showInfo,
} = useToast();

watch(
    () => page.props.flash,
    (flash) => {
        if (flash.success) {
            showSuccess(flash.success);
        }
        if (flash.error) {
            showError(flash.error);
        }
        if (flash.warning) {
            showWarning(flash.warning);
        }
        if (flash.info) {
            showInfo(flash.info);
        }
    },
    { deep: true, immediate: true },
);

/**
 * Inject marketing tool scripts into the head tag
 */
const injectMarketingTools = () => {
    marketingTools.value.forEach((tool) => {
        if (tool.script_code) {
            const scriptId = `marketing-tool-${tool.id}`;
            // Check if already injected to prevent duplicates
            if (!document.getElementById(scriptId)) {
                try {
                    const range = document.createRange();
                    const fragment = range.createContextualFragment(
                        tool.script_code,
                    );

                    // Create a wrapper to identify the scripts
                    const wrapper = document.createElement("div");
                    wrapper.id = scriptId;
                    wrapper.style.display = "none";
                    wrapper.appendChild(fragment);
                    document.head.appendChild(wrapper);
                } catch (error) {
                    console.error(
                        `Failed to inject marketing tool: ${tool.tool_name}`,
                        error,
                    );
                }
            }
        }
    });
};
</script>

<template>
    <Head>
        <link v-if="media?.favicon" rel="icon" :href="media.favicon" />
    </Head>


    <div class="min-h-screen flex flex-col bg-white">
        <!-- Header -->
        <FrontendHeader
            :categories="categories"
            :settings="settings"
            @toggle-mobile-menu="toggleMobileMenu"
        />

        <!-- Mobile Menu Overlay -->
        <MobileMenu
            :is-open="isMobileMenuOpen"
            :categories="categories"
            @close="closeMobileMenu"
        />

        <!-- Main Content -->
        <main class="flex-1">
            <slot />
        </main>

        <!-- Footer -->
        <div :class="route().current('home') ? '' : 'hidden md:block'">
            <FrontendFooter
                :settings="settings"
                :pages="pages"
                :categories="categories"
            />
        </div>

        <!-- Toast Notifications -->
        <Toast />

        <Toaster />
    </div>
</template>

<style scoped>
@keyframes loading-bar {
    0% {
        transform: translateX(-100%);
    }
    100% {
        transform: translateX(100%);
    }
}

.animate-loading-bar {
    animation: loading-bar 1.5s infinite linear;
}

/* Ensure smooth transitions */
.fade-enter-active,
.fade-leave-active {
    transition: opacity 0.3s ease;
}

.fade-enter-from,
.fade-leave-to {
    opacity: 0;
}
</style>
