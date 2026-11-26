<script setup>
import { computed } from "vue";
import { usePage } from "@inertiajs/vue3";

const props = defineProps({
    page: { type: Object, required: true },
});

// All contact details come from the admin general settings.
const g = computed(() => usePage().props.settings?.generalSettings || {});
const siteName = computed(() => g.value.app_name || "us");

const digits = (v) => String(v || "").replace(/[^\d+]/g, "");

const cards = computed(() => {
    const list = [];
    const phones = [g.value.phone_number, g.value.store_phone_number].filter(Boolean);
    if (phones.length) {
        list.push({
            title: "Call us",
            lines: [...new Set(phones)],
            href: `tel:${digits(phones[0])}`,
            icon: "M3 5a2 2 0 012-2h2.3a1 1 0 01.95.68l1.2 3.6a1 1 0 01-.5 1.2l-1.6.8a11 11 0 005.4 5.4l.8-1.6a1 1 0 011.2-.5l3.6 1.2a1 1 0 01.68.95V19a2 2 0 01-2 2A16 16 0 013 5z",
        });
    }
    if (g.value.whatsapp_number) {
        list.push({
            title: "WhatsApp",
            lines: [g.value.whatsapp_number],
            href: `https://wa.me/${digits(g.value.whatsapp_number).replace("+", "")}`,
            external: true,
            icon: "M21 12a9 9 0 01-13.4 7.8L3 21l1.3-4.4A9 9 0 1121 12zM9 9.5c0 3 2.5 5.5 5.5 5.5l1-1.5-2-1-.8.8a4 4 0 01-1.9-1.9l.8-.8-1-2L9 9.5z",
        });
    }
    if (g.value.store_email) {
        list.push({
            title: "Email us",
            lines: [g.value.store_email],
            href: `mailto:${g.value.store_email}`,
            icon: "M3 7a2 2 0 012-2h14a2 2 0 012 2v10a2 2 0 01-2 2H5a2 2 0 01-2-2V7zm0 0l9 6 9-6",
        });
    }
    if (g.value.address) {
        list.push({
            title: "Visit us",
            lines: [g.value.address],
            href: `https://www.google.com/maps/search/?api=1&query=${encodeURIComponent(g.value.address)}`,
            external: true,
            icon: "M12 21s-7-6.2-7-11a7 7 0 1114 0c0 4.8-7 11-7 11zm0-8.5a2.5 2.5 0 100-5 2.5 2.5 0 000 5z",
        });
    }
    return list;
});

const socials = computed(() =>
    [
        ["Facebook", g.value.facebook_url],
        ["Instagram", g.value.instagram_url],
        ["YouTube", g.value.youtube_url],
        ["TikTok", g.value.tiktok_url],
        ["X", g.value.x_url],
    ].filter(([, url]) => url),
);

const dark = "color-mix(in srgb, var(--primary-color) 65%, black)";
</script>

<template>
    <div class="pb-12 md:pb-20">
        <!-- Hero -->
        <section class="relative overflow-hidden text-white" :style="{ background: dark }">
            <div class="container mx-auto px-4 py-12 text-center md:py-20">
                <p class="text-xs font-semibold uppercase tracking-[0.2em] md:text-sm" style="color: color-mix(in srgb, var(--primary-color) 45%, #c6f26b)">
                    Contact Us
                </p>
                <h1 class="mx-auto mt-3 max-w-2xl font-serif text-3xl font-semibold leading-tight md:text-5xl">
                    We'd love to hear from you.
                </h1>
                <p class="mx-auto mt-4 max-w-xl text-sm text-white/80 md:text-base">
                    Questions about our sauces, an order, or a wholesale enquiry? Reach {{ siteName }} any way that suits you.
                </p>
            </div>
        </section>

        <!-- Info cards, overlapping the hero -->
        <section class="container relative z-10 mx-auto -mt-8 px-4 md:-mt-12">
            <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                <component
                    :is="c.href ? 'a' : 'div'"
                    v-for="c in cards"
                    :key="c.title"
                    :href="c.href"
                    :target="c.external ? '_blank' : null"
                    :rel="c.external ? 'noopener noreferrer' : null"
                    class="group rounded-2xl border border-primary bg-white p-6 text-center shadow-[0_10px_30px_rgba(0,0,0,0.07)] transition duration-300 hover:-translate-y-1 hover:shadow-[0_16px_36px_rgba(0,0,0,0.12)]"
                >
                    <span class="mx-auto flex h-14 w-14 items-center justify-center rounded-full bg-primary text-white">
                        <svg class="h-6 w-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                            <path :d="c.icon" />
                        </svg>
                    </span>
                    <h3 class="mt-4 text-base font-bold text-gray-900">{{ c.title }}</h3>
                    <p v-for="line in c.lines" :key="line" class="mt-1 break-words text-sm leading-relaxed text-gray-600">{{ line }}</p>
                </component>
            </div>
        </section>

        <!-- Page content from the admin + social links -->
        <section class="container mx-auto mt-10 px-4 md:mt-14">
            <div class="mx-auto max-w-3xl">
                <div v-if="socials.length" class="text-center">
                    <p class="text-sm font-semibold uppercase tracking-wider text-gray-700">Follow us</p>
                    <div class="mt-4 flex flex-wrap justify-center gap-3">
                        <a
                            v-for="[label, url] in socials"
                            :key="label"
                            :href="url"
                            target="_blank"
                            rel="noopener noreferrer"
                            class="rounded-full border border-primary px-5 py-2 text-sm font-semibold text-primary transition-colors hover:bg-primary hover:text-white"
                        >{{ label }}</a>
                    </div>
                </div>
            </div>
        </section>
    </div>
</template>
