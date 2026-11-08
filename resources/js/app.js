import "./bootstrap";

import { createInertiaApp, router } from "@inertiajs/vue3";
import { resolvePageComponent } from "laravel-vite-plugin/inertia-helpers";
import { createApp, h } from "vue";
import { ZiggyVue } from "../../vendor/tightenco/ziggy";
import "@steveyuowo/vue-hot-toast/vue-hot-toast.css";
import { initMetaPixel, useMetaPixel } from "@/Composables/useMetaPixel";

const appName = import.meta.env.VITE_APP_NAME || "";

// Must match the storefront font URL in app.blade.php exactly.
const STOREFRONT_FONTS = "family=Heebo:wght@400..900&family=Oswald:wght@500..700&family=Great+Vibes";

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
            // Pass logged-in user data for Advanced Matching.
            // Meta Pixel SDK hashes em/ph automatically via SHA-256.
            const authUser = props.initialPage.props.auth?.user;
            const userData = {};
            if (authUser?.email) userData.em = authUser.email;
            if (authUser?.phone_number) userData.ph = authUser.phone_number;
            if (authUser?.name) {
                const parts = authUser.name.trim().split(' ');
                if (parts[0]) userData.fn = parts[0];
                if (parts.length > 1) userData.ln = parts[parts.length - 1];
            }
            initMetaPixel(metaPixel.pixel_id, userData);
        }

        const vueApp = createApp({ render: () => h(App, props) })
            .use(plugin)
            .use(ZiggyVue)
            .mount(el);

        // Drop the static skeleton in the same tick Vue finishes mounting,
        // so the browser never paints the gap between "skeleton gone" and
        // "Vue content in" as two separate frames.
        document.getElementById("storefront-skeleton")?.remove();

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
    const families = storefront ? STOREFRONT_FONTS : 'family=Montserrat:wght@400..900';
    const href = `https://fonts.googleapis.com/css2?${families}&display=swap`;
    if (font && font.getAttribute('href') !== href) font.setAttribute('href', href);
    useMetaPixel().trackPageView();
});
