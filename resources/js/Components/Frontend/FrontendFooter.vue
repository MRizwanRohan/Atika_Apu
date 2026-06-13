<script setup>
import { ref, computed } from "vue";
import { Link } from "@inertiajs/vue3";
import { MessageSquareText, X } from "lucide-vue-next";

const props = defineProps({
    settings: {
        type: Object,
        default: () => ({}),
    },
    pages: {
        type: Array,
        default: () => [],
    },
    categories: {
        type: Array,
        default: () => [],
    },
});

const currentYear = computed(() => new Date().getFullYear());

// Support settings returned as { generalSettings, media, ... }
const general = computed(() => props.settings?.generalSettings || {});
const media = computed(() => props.settings?.media || {});

const siteName = computed(
    () =>
        general.value?.app_name ||
        general.value?.site_name ||
        props.settings?.siteName ||
        "E-Shop",
);
const siteDescription = computed(
    () =>
        general.value?.footer_text ||
        general.value?.site_description ||
        "Your premium destination for beauty and wellness. We provide curated high-end products to enhance your natural grace.",
);

const footerLogo = computed(
    () =>
        media.value?.footer_logo ||
        media.value?.footerLogo ||
        media.value?.logo ||
        null,
);
const facebookIframe = computed(() => {
    const html = general.value?.facebook_iframe || null;
    if (!html) return null;
    return html
        .replace(/width=["']\d+["']/gi, 'width="100%"')
        .replace(/height=["']\d+["']/gi, 'height="100%"')
        .replace(/<iframe/gi, '<iframe allow="unload"')
        .replace(
            /(style=["'])([^"']*)(["'])/gi,
            (match, prefix, style, suffix) => {
                const cleaned = `${style}; width:100% !important; height:100% !important;`;
                return `${prefix}${cleaned}${suffix}`;
            },
        );
});

// Social links
const facebookUrl = computed(() => general.value?.facebook_url || null);
const instagramUrl = computed(() => general.value?.instagram_url || null);
const youtubeUrl = computed(() => general.value?.youtube_url || null);
const tiktokUrl = computed(() => general.value?.tiktok_url || null);
const xUrl = computed(() => general.value?.x_url || null);

// Contact fields
const storeEmail = computed(() => general.value?.store_email || "");
const storePhone = computed(() => general.value?.store_phone_number || "");
const storeAddress = computed(() => general.value?.address || "");

const paymentMethodsImage = computed(
    () => media.value?.footer_payment_logo || null,
);

// Live chat integration
const facebookPageId = computed(() => general.value?.facebook_page_id || null);
const whatsappNumber = computed(() => general.value?.whatsapp_number || null);

const messengerLink = computed(() =>
    facebookPageId.value ? `https://m.me/${facebookPageId.value}` : null,
);
const whatsappLink = computed(() =>
    whatsappNumber.value ? `https://wa.me/${whatsappNumber.value}` : null,
);

// Footer sections
const categoriesList = computed(() => props.categories?.slice(0, 6) || []);
const pagesList = computed(() => props.pages || []);


const isChatOpen = ref(false);
const toggleChat = () => {
    isChatOpen.value = !isChatOpen.value;
};

// Scroll to top helper
const scrollToTop = () => {
    window.scrollTo({ top: 0, behavior: "smooth" });
};
</script>

<template>
    <footer
        class="bg-[#0a0a0a] text-zinc-400 font-['Jost',sans-serif] selection:bg-primary selection:text-white"
    >
        <!-- Newsletter Section -->

        <!-- <pre>{{ settings }}</pre> -->

        <!-- Main Footer Content -->
        <div class="container mx-auto px-4 py-20">
            <div
                class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-12 gap-12 lg:gap-8"
            >
                <!-- Brand Section -->
                <div class="lg:col-span-3 space-y-8">
                    <Link href="/" class="inline-block group">
                        <img
                            v-if="footerLogo"
                            :src="footerLogo"
                            alt="Footer Logo"
                            class="h-12 w-auto object-contain brightness-110 group-hover:scale-105 transition-transform duration-300"
                        />
                        <span
                            v-else
                            class="text-2xl font-bold text-white tracking-widest"
                            >{{ siteName }}</span
                        >
                    </Link>
                    <p class="text-zinc-500 leading-relaxed max-w-sm">
                        {{ siteDescription }}
                    </p>
                    <!-- Social Links -->
                    <div class="flex items-center gap-4">
                        <a
                            v-if="facebookUrl"
                            :href="facebookUrl"
                            target="_blank"
                            class="w-10 h-10 rounded-full border border-zinc-800 flex items-center justify-center hover:bg-primary hover:border-primary hover:text-white hover:-translate-y-1 transition-all duration-300"
                        >
                            <svg
                                class="w-5 h-5"
                                fill="currentColor"
                                viewBox="0 0 24 24"
                            >
                                <path
                                    d="M22 12c0-5.523-4.477-10-10-10S2 6.477 2 12c0 4.991 3.657 9.128 8.438 9.879v-6.99H7.898v-2.889h2.54V9.845c0-2.507 1.492-3.89 3.777-3.89 1.094 0 2.238.195 2.238.195v2.462h-1.26c-1.243 0-1.63.771-1.63 1.562v1.875h2.773l-.443 2.889h-2.33v6.99C18.343 21.128 22 16.991 22 12z"
                                />
                            </svg>
                        </a>
                        <a
                            v-if="instagramUrl"
                            :href="instagramUrl"
                            target="_blank"
                            class="w-10 h-10 rounded-full border border-zinc-800 flex items-center justify-center hover:bg-primary hover:border-primary hover:text-white hover:-translate-y-1 transition-all duration-300"
                        >
                            <svg
                                class="w-5 h-5"
                                fill="currentColor"
                                viewBox="0 0 24 24"
                            >
                                <path
                                    d="M7 2C4.243 2 2 4.243 2 7v10c0 2.757 2.243 5 5 5h10c2.757 0 5-2.243 5-5V7c0-2.757-2.243-5-5-5H7zm5 6a4 4 0 110 8 4 4 0 010-8zm6.5-.5a1 1 0 110 2 1 1 0 010-2z"
                                />
                            </svg>
                        </a>
                        <a
                            v-if="youtubeUrl"
                            :href="youtubeUrl"
                            target="_blank"
                            class="w-10 h-10 rounded-full border border-zinc-800 flex items-center justify-center hover:bg-primary hover:border-primary hover:text-white hover:-translate-y-1 transition-all duration-300"
                        >
                            <svg
                                class="w-5 h-5"
                                fill="currentColor"
                                viewBox="0 0 24 24"
                            >
                                <path
                                    d="M23.498 6.186a2.994 2.994 0 00-2.11-2.117C19.675 3.5 12 3.5 12 3.5s-7.675 0-9.388.569A2.994 2.994 0 00.5 6.186 31.25 31.25 0 000 12a31.25 31.25 0 00.5 5.814 2.994 2.994 0 002.112 2.117C4.325 20.5 12 20.5 12 20.5s7.675 0 9.388-.569a2.994 2.994 0 002.11-2.117A31.25 31.25 0 0024 12a31.25 31.25 0 00-.502-5.814zM9.75 15.02V8.98L15.5 12l-5.75 3.02z"
                                />
                            </svg>
                        </a>
                        <a
                            v-if="tiktokUrl"
                            :href="tiktokUrl"
                            target="_blank"
                            class="w-10 h-10 rounded-full border border-zinc-800 flex items-center justify-center hover:bg-primary hover:border-primary hover:text-white hover:-translate-y-1 transition-all duration-300"
                        >
                            <svg
                                class="w-5 h-5"
                                fill="currentColor"
                                viewBox="0 0 24 24"
                            >
                                <path
                                    d="M12.525.02c1.31 0 2.591.24 3.75.69v5.62c-1.28-.79-2.79-1.24-4.41-1.24a7.29 7.29 0 00-7.29 7.29c0 4.03 3.26 7.29 7.29 7.29 4.03 0 7.29-3.26 7.29-7.29V0h3.87c.13 2.11 1.25 4 2.93 5.22v4.86c-2.31 0-4.41-.83-6.05-2.22v8.52c0 5.4-4.38 9.77-9.77 9.77S.25 21.78.25 16.38 4.63 6.61 10.02 6.61c1.23 0 2.4.23 3.48.64V.02h-.97z"
                                />
                            </svg>
                        </a>
                    </div>
                </div>

                <!-- Support & Info -->
                <div class="lg:col-span-3">
                    <h4
                        class="text-white font-semibold uppercase tracking-wider text-sm mb-8"
                    >
                        Policy & Legal
                    </h4>
                    <ul class="space-y-4">
                        <li v-for="page in pagesList" :key="page.id">
                            <Link
                                :href="`/page/${page.slug}`"
                                class="hover:text-primary transition-all duration-300 inline-block hover:translate-x-1"
                            >
                                {{ page.name }}
                            </Link>
                        </li>
                    </ul>
                </div>

                <!-- Contact & Info -->
                <div class="lg:col-span-3 space-y-6">
                    <h4
                        class="text-white font-semibold uppercase tracking-wider text-sm mb-8"
                    >
                        Get In Touch
                    </h4>
                    <div class="space-y-4">
                        <div
                            v-if="storeAddress"
                            class="flex items-start gap-4 group"
                        >
                            <div
                                class="w-10 h-10 shrink-0 rounded-lg bg-zinc-900 flex items-center justify-center border border-zinc-800 group-hover:border-primary/50 group-hover:text-primary transition-all duration-300"
                            >
                                <svg
                                    class="w-5 h-5"
                                    fill="none"
                                    stroke="currentColor"
                                    viewBox="0 0 24 24"
                                >
                                    <path
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                        stroke-width="1.5"
                                        d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"
                                    />
                                    <path
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                        stroke-width="1.5"
                                        d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"
                                    />
                                </svg>
                            </div>
                            <div>
                                <span
                                    class="block text-xs uppercase tracking-widest text-zinc-600 mb-1"
                                    >Our Location</span
                                >
                                <p class="text-zinc-300 leading-relaxed">
                                    {{ storeAddress }}
                                </p>
                            </div>
                        </div>
                        <div
                            v-if="storePhone"
                            class="flex items-start gap-4 group"
                        >
                            <div
                                class="w-10 h-10 shrink-0 rounded-lg bg-zinc-900 flex items-center justify-center border border-zinc-800 group-hover:border-primary/50 group-hover:text-primary transition-all duration-300"
                            >
                                <svg
                                    class="w-5 h-5"
                                    fill="none"
                                    stroke="currentColor"
                                    viewBox="0 0 24 24"
                                >
                                    <path
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                        stroke-width="1.5"
                                        d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"
                                    />
                                </svg>
                            </div>
                            <div>
                                <span
                                    class="block text-xs uppercase tracking-widest text-zinc-600 mb-1"
                                    >Phone Number</span
                                >
                                <a
                                    :href="`tel:${storePhone}`"
                                    class="text-zinc-300 hover:text-primary transition-colors text-lg font-medium"
                                    >{{ storePhone }}</a
                                >
                            </div>
                        </div>
                        <div
                            v-if="storeEmail"
                            class="flex items-start gap-4 group"
                        >
                            <div
                                class="w-10 h-10 shrink-0 rounded-lg bg-zinc-900 flex items-center justify-center border border-zinc-800 group-hover:border-primary/50 group-hover:text-primary transition-all duration-300"
                            >
                                <svg
                                    class="w-5 h-5"
                                    fill="none"
                                    stroke="currentColor"
                                    viewBox="0 0 24 24"
                                >
                                    <path
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                        stroke-width="1.5"
                                        d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"
                                    />
                                </svg>
                            </div>
                            <div>
                                <span
                                    class="block text-xs uppercase tracking-widest text-zinc-600 mb-1"
                                    >Email Address</span
                                >
                                <a
                                    :href="`mailto:${storeEmail}`"
                                    class="text-zinc-300 hover:text-primary transition-colors"
                                    >{{ storeEmail }}</a
                                >
                            </div>
                        </div>
                    </div>
                </div>

                <div v-if="facebookIframe" class="lg:col-span-3">
                    <h4
                        class="text-white font-semibold uppercase tracking-wider text-sm mb-8"
                    >
                        Facebook
                    </h4>
                    <div
                        class="overflow-hidden rounded-2xl border border-zinc-800 bg-zinc-950"
                    >
                        <div class="facebook-iframe-wrapper overflow-hidden">
                            <div
                                class="facebook-iframe-content h-full"
                                v-html="facebookIframe"
                            ></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Bottom Bar -->
        <div class="border-t border-zinc-800/50 bg-[#050505]">
            <div class="container mx-auto px-4 py-8">
                <div
                    class="flex flex-col lg:flex-row items-center justify-between gap-8"
                >
                    <!-- Copyright -->
                    <div class="text-center lg:text-left">
                        <p class="text-sm text-zinc-500">
                            © {{ currentYear }}
                            <span class="text-zinc-200 font-semibold">{{
                                siteName
                            }}</span
                            >. All rights reserved.
                            <br class="md:hidden" />
                            Crafted by
                            <a
                                href="https://auxtechbd.com"
                                target="_blank"
                                class="text-primary hover:underline"
                                rel="noopener noreferrer"
                                >Auxtech</a
                            >
                        </p>
                    </div>

                    <!-- Payment Methods -->
                    <div class="flex items-center gap-6">
                        <span
                            class="text-xs uppercase tracking-widest text-zinc-600"
                            >Secure Payments</span
                        >
                        <div
                            v-if="paymentMethodsImage"
                            class="h-8 opacity-70 hover:opacity-100 transition-opacity"
                        >
                            <img
                                :src="paymentMethodsImage"
                                alt="Payment Methods"
                                class="h-full object-contain filter grayscale brightness-200"
                            />
                        </div>
                        <div v-else class="flex gap-2">
                            <div
                                class="w-10 h-6 bg-zinc-800/50 rounded border border-zinc-700/50 flex items-center justify-center"
                            >
                                <img
                                    src="https://img.icons8.com/color/48/visa.png"
                                    alt="Visa"
                                    class="h-4"
                                />
                            </div>
                            <div
                                class="w-10 h-6 bg-zinc-800/50 rounded border border-zinc-700/50 flex items-center justify-center"
                            >
                                <img
                                    src="https://img.icons8.com/color/48/mastercard.png"
                                    alt="MC"
                                    class="h-4"
                                />
                            </div>
                            <div
                                class="w-10 h-6 bg-zinc-800/50 rounded border border-zinc-700/50 flex items-center justify-center"
                            >
                                <img
                                    src="https://img.icons8.com/color/48/amex.png"
                                    alt="Amex"
                                    class="h-4"
                                />
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </footer>

    <!-- Live Chat & scroll to top Container -->
    <div
        v-if="route().current() !== 'order.create'"
        class="fixed bottom-16 right-4 md:bottom-8 md:right-8 z-[100] flex flex-col items-end gap-3 md:gap-4"
    >
        <!-- Scroll to Top Button (Small) -->
        <!-- <button
                @click="scrollToTop"
                class="flex items-center justify-center w-8 h-8 md:w-10 md:h-10 rounded-full bg-zinc-900/80   text-zinc-400 border border-zinc-800 hover:text-white hover:border-primary/50 transition-all duration-300 group"
                aria-label="Scroll to top"
            >
                <svg class="w-4 h-4 md:w-5 md:h-5 transition-transform group-hover:-translate-y-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 10l7-7m0 0l7 7m-7-7v18" />
                </svg>
            </button> -->

        <!-- Chat Options Menu -->
        <Transition
            enter-active-class="transition duration-300 ease-out"
            enter-from-class="transform scale-95 opacity-0 translate-y-4"
            enter-to-class="transform scale-100 opacity-100 translate-y-0"
            leave-active-class="transition duration-200 ease-in"
            leave-from-class="transform scale-100 opacity-100 translate-y-0"
            leave-to-class="transform scale-95 opacity-0 translate-y-4"
        >
            <div v-if="isChatOpen" class="flex flex-col gap-4 mb-2">
                <!-- WhatsApp -->
                <a
                    v-if="whatsappLink"
                    :href="whatsappLink"
                    target="_blank"
                    class="flex items-center justify-center w-12 h-12 md:w-14 md:h-14 rounded-full bg-[#25D366] text-white shadow-2xl transition-all duration-300 hover:scale-110 hover:-rotate-12 group relative"
                    aria-label="Chat on WhatsApp"
                >
                    <svg
                        class="w-6 h-6 md:w-8 md:h-8"
                        fill="currentColor"
                        viewBox="0 0 24 24"
                    >
                        <path
                            d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L0 24l6.335-1.662c1.72.937 3.672 1.433 5.66 1.433h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413z"
                        />
                    </svg>
                    <span
                        class="absolute right-full mr-3 md:mr-4 px-2 md:px-3 py-1 md:py-1.5 bg-white text-[#25D366] text-[10px] md:text-xs font-bold rounded-lg shadow-xl whitespace-nowrap"
                    >
                        WhatsApp
                    </span>
                </a>

                <!-- Messenger -->
                <a
                    v-if="messengerLink"
                    :href="messengerLink"
                    target="_blank"
                    class="flex items-center justify-center w-12 h-12 md:w-14 md:h-14 rounded-full bg-gradient-to-tr from-[#0084FF] to-[#00C6FF] text-white shadow-2xl transition-all duration-300 hover:scale-110 hover:rotate-12 group relative"
                    aria-label="Chat on Messenger"
                >
                    <MessageSquareText class="w-6 h-6 md:w-8 md:h-8" />
                    <span
                        class="absolute right-full mr-3 md:mr-4 px-2 md:px-3 py-1 md:py-1.5 bg-white text-[#0084FF] text-[10px] md:text-xs font-bold rounded-lg shadow-xl whitespace-nowrap"
                    >
                        Messenger
                    </span>
                </a>
            </div>
        </Transition>

        <!-- Main Toggle Button -->
        <button
            @click="toggleChat"
            class="flex items-center justify-center w-12 h-12 md:w-16 md:h-16 rounded-full shadow-2xl transition-all duration-500 hover:scale-105 active:scale-95 group relative border-2 md:border-4 border-zinc-900/50 overflow-hidden"
            :class="
                isChatOpen ? 'bg-zinc-800 text-white' : 'bg-primary text-white'
            "
            aria-label="Toggle chat menu"
        >
            <div class="relative z-10">
                <MessageSquareText
                    v-if="!isChatOpen"
                    class="w-6 h-6 md:w-8 md:h-8 transition-all duration-300"
                />
                <X
                    v-else
                    class="w-6 h-6 md:w-8 md:h-8 transition-all duration-300 rotate-90"
                />
            </div>

            <!-- Animated Background Background -->
            <div
                v-if="!isChatOpen"
                class="absolute inset-0 bg-primary-dark/20 animate-pulse"
            ></div>

            <!-- Ping Effect when closed -->
            <span
                v-if="!isChatOpen"
                class="absolute inset-0 rounded-full bg-primary animate-ping opacity-40"
            ></span>
        </button>
    </div>
</template>

<style scoped>
footer {
    perspective: 1000px;
}

/* Custom scrollbar for footer if needed */
footer ::-webkit-scrollbar {
    width: 4px;
}

footer ::-webkit-scrollbar-track {
    background: transparent;
}

footer ::-webkit-scrollbar-thumb {
    background: #333;
    border-radius: 2px;
}

@media (max-width: 1024px) {
    .container {
        max-width: 100%;
    }
}

.facebook-iframe-wrapper,
.facebook-iframe-wrapper > .facebook-iframe-content,
.facebook-iframe-wrapper iframe {
    width: 100% !important;
    min-height: 100% !important;
    height: 100% !important;
}

.facebook-iframe-wrapper iframe {
    display: block !important;
}
</style>
