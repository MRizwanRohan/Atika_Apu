import "../css/app.css";
import "./bootstrap";

import { createInertiaApp, router } from "@inertiajs/vue3";
import { resolvePageComponent } from "laravel-vite-plugin/inertia-helpers";
import { createApp, h } from "vue";
import { ZiggyVue } from "../../vendor/tightenco/ziggy";
import "@steveyuowo/vue-hot-toast/vue-hot-toast.css";
import { initMetaPixel, useMetaPixel } from "@/Composables/useMetaPixel";

const appName = import.meta.env.VITE_APP_NAME || "";

createInertiaApp({
    title: (title) => (appName ? `${title}` : title),
    resolve: (name) =>
        resolvePageComponent(
            `./Pages/${name}.vue`,
            import.meta.glob("./Pages/**/*.vue")
        ),
    setup({ el, App, props, plugin }) {
        // Initialize before Success.vue's onMounted sends the purchase.
        const metaPixel = props.initialPage.props.metaPixel;
        if (metaPixel?.pixel_enabled && metaPixel.pixel_id) {
            initMetaPixel(metaPixel.pixel_id);
        }

        const vueApp = createApp({ render: () => h(App, props) })
            .use(plugin)
            .use(ZiggyVue)
            .mount(el);

        return vueApp;
    },
    progress: {
        color: "#db0033",
        showSpinner: true,
    },
});

// ── SPA PageView: track every Inertia navigation ──
router.on('navigate', ({ detail: { page } }) => {
    // Inertia navigation does not reload the Blade head when crossing site areas.
    const storefront = /^(Frontend|User|PreOrderLead)\//.test(page.component);
    const font = document.getElementById('site-font-stylesheet');
    const family = storefront ? 'Heebo' : 'Montserrat';
    const href = `https://fonts.googleapis.com/css2?family=${family}:wght@400..900&display=swap`;
    if (font && font.getAttribute('href') !== href) font.setAttribute('href', href);
    useMetaPixel().trackPageView();
});
