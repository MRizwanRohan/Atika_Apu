<script setup>
import { ref, computed } from "vue";
import { Link, usePage } from "@inertiajs/vue3";
import { MessageSquareText, X } from "lucide-vue-next";
import footerBg from "@/../images/footer-bg.webp";

const props = defineProps({
    settings: {
        type: Object,
        default: () => ({}),
    },
    pages: {
        type: Array,
        default: () => [],
    },
});

const page = usePage();
const currentYear = computed(() => new Date().getFullYear());

// Everything below comes from the admin panel (settings, media, site pages, sauces).
const general = computed(() => props.settings?.generalSettings || {});
const media = computed(() => props.settings?.media || {});

const siteName = computed(
    () => general.value?.app_name || general.value?.site_name || "Store",
);
const tagline = computed(() => general.value?.footer_text || "");
const logo = computed(() => media.value?.logo || null);
const paymentImage = computed(() => media.value?.footer_payment_logo || null);

const sauces = computed(() => (page.props.navSauces || []).slice(0, 7));
// A page with no footer column chosen stays in Help, where every page used to be listed.
const helpPages = computed(() => props.pages.filter((p) => (p.footer_group || "help") === "help"));
const aboutPages = computed(() => props.pages.filter((p) => p.footer_group === "about"));

const socialIcons = {
    facebook: "M22 12c0-5.523-4.477-10-10-10S2 6.477 2 12c0 4.991 3.657 9.128 8.438 9.879v-6.99H7.898v-2.889h2.54V9.845c0-2.507 1.492-3.89 3.777-3.89 1.094 0 2.238.195 2.238.195v2.462h-1.26c-1.243 0-1.63.771-1.63 1.562v1.875h2.773l-.443 2.889h-2.33v6.99C18.343 21.128 22 16.991 22 12z",
    instagram: "M7 2C4.243 2 2 4.243 2 7v10c0 2.757 2.243 5 5 5h10c2.757 0 5-2.243 5-5V7c0-2.757-2.243-5-5-5H7zm5 6a4 4 0 110 8 4 4 0 010-8zm6.5-.5a1 1 0 110 2 1 1 0 010-2z",
    youtube: "M23.498 6.186a2.994 2.994 0 00-2.11-2.117C19.675 3.5 12 3.5 12 3.5s-7.675 0-9.388.569A2.994 2.994 0 00.5 6.186 31.25 31.25 0 000 12a31.25 31.25 0 00.5 5.814 2.994 2.994 0 002.112 2.117C4.325 20.5 12 20.5 12 20.5s7.675 0 9.388-.569a2.994 2.994 0 002.11-2.117A31.25 31.25 0 0024 12a31.25 31.25 0 00-.502-5.814zM9.75 15.02V8.98L15.5 12l-5.75 3.02z",
    tiktok: "M12.525.02c1.31 0 2.591.24 3.75.69v5.62c-1.28-.79-2.79-1.24-4.41-1.24a7.29 7.29 0 00-7.29 7.29c0 4.03 3.26 7.29 7.29 7.29 4.03 0 7.29-3.26 7.29-7.29V0h3.87c.13 2.11 1.25 4 2.93 5.22v4.86c-2.31 0-4.41-.83-6.05-2.22v8.52c0 5.4-4.38 9.77-9.77 9.77S.25 21.78.25 16.38 4.63 6.61 10.02 6.61c1.23 0 2.4.23 3.48.64V.02h-.97z",
    x: "M18.244 2.25h3.308l-7.227 8.26 8.502 11.24H16.17l-5.214-6.817L4.99 21.75H1.68l7.73-8.835L1.254 2.25H8.08l4.713 6.231zm-1.161 17.52h1.833L7.084 4.126H5.117z",
};
const socials = computed(() =>
    [
        ["facebook", "Facebook", general.value?.facebook_url],
        ["instagram", "Instagram", general.value?.instagram_url],
        ["youtube", "YouTube", general.value?.youtube_url],
        ["tiktok", "TikTok", general.value?.tiktok_url],
        ["x", "X", general.value?.x_url],
    ]
        .filter(([, , url]) => url)
        .map(([key, label, url]) => ({ key, label, url, path: socialIcons[key] })),
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

const isChatOpen = ref(false);
const toggleChat = () => {
    isChatOpen.value = !isChatOpen.value;
};

const linkClass = "text-[14px] leading-[22px] text-white/75 transition-colors hover:text-white";
</script>

<template>
    <footer class="relative isolate bg-[#264305] text-white/80">
        <!-- The artwork is decorated on both edges; each half shows its own edge at full height, so neither is cropped. -->
        <div class="absolute inset-y-0 left-0 -z-10 w-1/2" :style="{ background: `url(${footerBg}) left bottom / auto 100% no-repeat` }" aria-hidden="true" />
        <div class="absolute inset-y-0 right-0 -z-10 w-1/2" :style="{ background: `url(${footerBg}) right bottom / auto 100% no-repeat` }" aria-hidden="true" />

        <div class="container mx-auto px-4 pb-24 pt-12 md:px-12 md:pb-6 md:pt-14 lg:px-36">
            <div class="grid grid-cols-2 gap-x-6 gap-y-10 md:grid-cols-4 md:gap-0">
                <!-- Brand -->
                <div class="col-span-2 md:col-span-1 md:pr-8">
                    <Link href="/" class="inline-block">
                        <img
                            v-if="logo"
                            :src="logo"
                            :alt="siteName"
                            class="h-14 w-auto max-w-[200px] object-contain"
                        />
                        <span v-else class="text-2xl font-black tracking-tighter text-white">{{ siteName }}</span>
                    </Link>

                    <p v-if="tagline" class="mt-5 max-w-[16.5rem] whitespace-pre-line text-[14px] leading-[22px] text-white/85">{{ tagline }}</p>

                    <div v-if="socials.length" class="mt-6 flex items-center gap-3">
                        <a
                            v-for="social in socials"
                            :key="social.key"
                            :href="social.url"
                            target="_blank"
                            rel="noopener noreferrer"
                            :aria-label="social.label"
                            class="flex h-10 w-10 items-center justify-center rounded-full border border-white/40 text-white transition-colors hover:border-white hover:bg-white hover:text-[#3d5a2c]"
                        >
                            <svg class="h-[18px] w-[18px]" fill="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                <path :d="social.path" />
                            </svg>
                        </a>
                    </div>
                </div>

                <!-- Shop -->
                <div class="md:border-l md:border-white/15 md:px-8">
                    <h4 class="text-sm font-bold uppercase tracking-wide text-white">Shop</h4>
                    <ul class="mt-4 space-y-0.5">
                        <li><Link :href="route('category.show', 'sauce')" :class="linkClass">All Sauces</Link></li>
                        <li v-for="sauce in sauces" :key="sauce.id">
                            <Link :href="route('product.details', sauce.slug)" :class="linkClass">{{ sauce.name }}</Link>
                        </li>
                    </ul>
                </div>

                <!-- Help -->
                <div class="md:border-l md:border-white/15 md:px-8">
                    <h4 class="text-sm font-bold uppercase tracking-wide text-white">Help</h4>
                    <ul class="mt-4 space-y-0.5">
                        <li><Link :href="route('user.dashboard')" :class="linkClass">My Account</Link></li>
                        <li><Link href="/order/track" :class="linkClass">Track Order</Link></li>
                        <li v-for="item in helpPages" :key="item.id">
                            <Link :href="route('pages.show', item.slug)" :class="linkClass">{{ item.name }}</Link>
                        </li>
                    </ul>
                </div>

                <!-- About -->
                <div v-if="aboutPages.length" class="md:border-l md:border-white/15 md:pl-8">
                    <h4 class="text-sm font-bold uppercase tracking-wide text-white">About</h4>
                    <ul class="mt-4 space-y-0.5">
                        <li v-for="item in aboutPages" :key="item.id">
                            <Link :href="route('pages.show', item.slug)" :class="linkClass">{{ item.name }}</Link>
                        </li>
                    </ul>
                </div>
            </div>

            <div class="mt-10 border-t border-white/15 pt-6 text-center text-sm text-white/75 md:mt-8">
                <img v-if="paymentImage" :src="paymentImage" alt="Payment methods" class="mx-auto mb-4 h-7 object-contain opacity-80" />
                <p>© {{ currentYear }} {{ siteName }}. All rights reserved.</p>
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
                <!-- <MessageSquareText
                    v-if="!isChatOpen"
                    class="w-6 h-6 md:w-8 md:h-8 transition-all duration-300"
                /> -->
                <div v-if="!isChatOpen">
                    <svg
                        xmlns="http://www.w3.org/2000/svg"
                        version="1.1"
                        xmlns:xlink="http://www.w3.org/1999/xlink"
                        width="30"
                        height="30"
                        x="0"
                        y="0"
                        viewBox="0 0 373.232 373.232"
                        style="enable-background: new 0 0 512 512"
                        xml:space="preserve"
                        class=""
                    >
                        <g>
                            <path
                                d="M187.466 0c-.1.1-.3.1-.6.1-101.2 0-183.5 82.3-183.5 183.5 0 41.3 14.1 81.4 39.9 113.7l-26.7 62c-2.2 5.1.2 11 5.2 13.1 1.8.8 3.8 1 5.7.7l97.9-17.2c19.6 7.1 40.2 10.7 61 10.6 101.2 0 183.5-82.3 183.5-183.5.2-100.9-81.5-182.9-182.4-183zm-1 346.6c-19.3 0-38.4-3.5-56.5-10.3-1.7-.7-3.5-.8-5.3-.5l-82.4 14.4 21.8-50.7c1.5-3.5.9-7.6-1.6-10.5-11.8-13.7-21.2-29.3-27.8-46.2-7.4-18.9-11.2-39-11.2-59.3 0-90.2 73.4-163.5 163.5-163.5 89.9-.2 162.9 72.5 163 162.4v.6c0 90.3-73.4 163.6-163.5 163.6z"
                                fill="currentColor"
                                opacity="1"
                                data-original="currentColor"
                            ></path>
                            <path
                                d="M178.666 146.7h-54c-5.5 0-10 4.5-10 10s4.5 10 10 10h54c5.5 0 10-4.5 10-10s-4.5-10-10-10zM248.666 196.7h-124c-5.5 0-10 4.5-10 10s4.5 10 10 10h124c5.5 0 10-4.5 10-10s-4.5-10-10-10z"
                                fill="currentColor"
                                opacity="1"
                                data-original="currentColor"
                            ></path>
                        </g>
                    </svg>
                </div>
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
