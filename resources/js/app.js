import "../css/app.css";
import "./bootstrap";

import { createInertiaApp, router } from "@inertiajs/vue3";
import { resolvePageComponent } from "laravel-vite-plugin/inertia-helpers";
import { createApp, h } from "vue";
import { ZiggyVue } from "../../vendor/tightenco/ziggy";
import "@steveyuowo/vue-hot-toast/vue-hot-toast.css";
import VueApexCharts from 'vue3-apexcharts';
import { initMetaPixel, shouldTrackMetaPixel } from "@/Composables/useMetaPixel";

const appName = import.meta.env.VITE_APP_NAME || "";

createInertiaApp({
    title: (title) => (appName ? `${title}` : title),
    resolve: (name) =>
        resolvePageComponent(
            `./Pages/${name}.vue`,
            import.meta.glob("./Pages/**/*.vue")
        ),
    setup({ el, App, props, plugin }) {
        const vueApp = createApp({ render: () => h(App, props) })
            .use(plugin)
            .use(ZiggyVue)
            .use(VueApexCharts)
            .mount(el);

        if (!shouldTrackMetaPixel()) {
            return vueApp;
        }

        // ── Meta Pixel: boot after app mounts ──
        // Fetch pixel config from backend (no access token exposed)
        fetch('/api/meta/pixel-config')
            .then(res => res.json())
            .then(config => {
                if (config.pixel_enabled && config.pixel_id) {
                    initMetaPixel(config.pixel_id);
                }
            })
            .catch(() => {}); // Silently fail — tracking is non-critical

        return vueApp;
    },
    progress: {
        color: "#db0033",
        showSpinner: true,
    },
});

// ── SPA PageView: track every Inertia navigation ──
router.on('navigate', () => {
    if (
        shouldTrackMetaPixel() &&
        typeof window !== 'undefined' &&
        typeof window.fbq === 'function'
    ) {
        window.fbq('track', 'PageView');
    }
});
