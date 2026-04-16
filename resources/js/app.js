import "../css/app.css";
import "./bootstrap";

import { createInertiaApp, router } from "@inertiajs/vue3";
import { resolvePageComponent } from "laravel-vite-plugin/inertia-helpers";
import { createApp, h } from "vue";
import { ZiggyVue } from "../../vendor/tightenco/ziggy";
import "@steveyuowo/vue-hot-toast/vue-hot-toast.css";
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
            .mount(el);

        const metaPixel = props.initialPage.props.metaPixel;
        if (metaPixel && metaPixel.pixel_enabled && metaPixel.pixel_id) {
            initMetaPixel(metaPixel.pixel_id);
        }

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
