<script setup>
import { ref, reactive } from "vue";
import { Head, router } from "@inertiajs/vue3";
import AdminLayout from "@/Layouts/AdminLayout.vue";
import { toast } from "@steveyuowo/vue-hot-toast";
import axios from "axios";

const props = defineProps({
    settings: Object,
});

const form = reactive({
    pixel_id:           props.settings?.pixel_id || "",
    access_token:       props.settings?.access_token || "",
    test_event_code:    props.settings?.test_event_code || "",
    api_version:        props.settings?.api_version || "v22.0",
    pixel_enabled:      props.settings?.pixel_enabled || false,
    capi_enabled:       props.settings?.capi_enabled || false,
    test_mode:          props.settings?.test_mode || false,
    purchase_fire_mode: props.settings?.purchase_fire_mode || "on_order_created",
});

const saving     = ref(false);
const testing    = ref(false);
const showToken  = ref(false);

const fireModes = [
    { value: "on_order_created", label: "🔔 When Order is Placed (immediately)", description: "Fires the moment a customer places an order." },
    { value: "on_confirmed",     label: "✅ When Order is Confirmed", description: "Admin must confirm the order first." },
    { value: "on_delivered",     label: "📦 When Order is Delivered", description: "Fires only after delivery is confirmed." },
    { value: "manual_only",      label: "🖱️ Manual Only", description: "Admin fires events from the order table manually." },
    { value: "disabled",         label: "🚫 Disabled", description: "No automatic Purchase events will fire." },
];

async function saveSettings() {
    saving.value = true;
    try {
        await axios.post(route("admin.meta-tracking.settings.update"), form);
        toast.success("Tracking settings saved successfully!");
    } catch (err) {
        const errors = err.response?.data?.errors;
        if (errors) {
            Object.values(errors).flat().forEach(msg => toast.error(msg));
        } else {
            toast.error("Failed to save settings.");
        }
    } finally {
        saving.value = false;
    }
}

async function sendTestEvent() {
    testing.value = true;
    try {
        const { data } = await axios.post(route("admin.meta-tracking.test-event"));
        if (data.success) {
            toast.success("Test event sent! Check your Meta Events Manager.");
        } else {
            toast.error("Test failed: " + data.message);
        }
    } catch (err) {
        toast.error(err.response?.data?.message || "Test event failed.");
    } finally {
        testing.value = false;
    }
}
</script>

<template>
    <Head title="Meta Tracking Settings" />
    <AdminLayout>
        <div class="max-w-4xl mx-auto py-8 px-4">
            <!-- Header -->
            <div class="mb-8 flex flex-col md:flex-row items-center justify-between">
                <div>
                    <h1 class="text-xl font-bold text-gray-900 flex items-center gap-2">
                        <span class="text-blue-600">📡</span> Meta Tracking Settings
                    </h1>
                    <p class="text-gray-500 text-sm mt-1">Configure Meta Pixel (browser) and Conversion API (server-side) tracking.</p>
                </div>
                <a :href="route('admin.meta-tracking.logs')" class="btn btn-sm btn-outline">
                    📋 View Event Logs
                </a>
            </div>

            <form @submit.prevent="saveSettings" class="space-y-6">
                <!-- PIXEL CREDENTIALS -->
                <div class="card bg-base-100 border border-base-300 shadow-sm">
                    <div class="card-body">
                        <h2 class="card-title text-base">🔑 Credentials</h2>

                        <div class="form-control">
                            <label class="label"><span class="label-text font-medium">Pixel ID</span></label>
                            <input
                                v-model="form.pixel_id"
                                type="text"
                                placeholder="e.g. 1234567890123456"
                                class="input input-bordered w-full"
                            />
                            <label class="label"><span class="label-text-alt text-gray-400">Found in Meta Business Suite → Events Manager → Your Pixel → Settings</span></label>
                        </div>

                        <div class="form-control mt-2">
                            <label class="label"><span class="label-text font-medium">Access Token (CAPI)</span></label>
                            <div class="flex gap-2">
                                <input
                                    v-model="form.access_token"
                                    :type="showToken ? 'text' : 'password'"
                                    placeholder="EAAxxxx..."
                                    class="input input-bordered flex-1"
                                    autocomplete="off"
                                />
                                <button type="button" @click="showToken = !showToken" class="btn btn-ghost btn-sm border border-base-300">
                                    {{ showToken ? '🙈 Hide' : '👁️ Show' }}
                                </button>
                            </div>
                            <label class="label"><span class="label-text-alt text-gray-400">Generate from Events Manager → Your Pixel → Settings → Generate Access Token</span></label>
                        </div>

                        <div class="form-control mt-2">
                            <label class="label"><span class="label-text font-medium">Test Event Code <span class="badge badge-sm badge-warning ml-1">optional</span></span></label>
                            <input
                                v-model="form.test_event_code"
                                type="text"
                                placeholder="e.g. TEST12345"
                                class="input input-bordered w-full"
                            />
                            <label class="label"><span class="label-text-alt text-gray-400">Found in Events Manager → Test Events tab. Only used when Test Mode is ON.</span></label>
                        </div>

                        <div class="form-control mt-2">
                            <label class="label"><span class="label-text font-medium">API Version</span></label>
                            <input
                                v-model="form.api_version"
                                type="text"
                                placeholder="v22.0"
                                class="input input-bordered w-full max-w-xs"
                            />
                        </div>
                    </div>
                </div>

                <!-- TOGGLES -->
                <div class="card bg-base-100 border border-base-300 shadow-sm">
                    <div class="card-body space-y-4">
                        <h2 class="card-title text-base">⚙️ Enable / Disable</h2>

                        <div class="flex items-center justify-between py-2 border-b border-base-200">
                            <div>
                                <p class="font-medium text-sm">Browser Pixel</p>
                                <p class="text-xs text-gray-400">Loads fbq() script in visitor's browser</p>
                            </div>
                            <input v-model="form.pixel_enabled" type="checkbox" class="toggle toggle-primary" />
                        </div>

                        <div class="flex items-center justify-between py-2 border-b border-base-200">
                            <div>
                                <p class="font-medium text-sm">Server-Side Conversion API (CAPI)</p>
                                <p class="text-xs text-gray-400">Sends events from your server — bypasses ad blockers</p>
                            </div>
                            <input v-model="form.capi_enabled" type="checkbox" class="toggle toggle-success" />
                        </div>

                        <div class="flex items-center justify-between py-2">
                            <div>
                                <p class="font-medium text-sm">Test Mode</p>
                                <p class="text-xs text-gray-400">Uses Test Event Code — events appear in Meta Test Events tab only</p>
                            </div>
                            <input v-model="form.test_mode" type="checkbox" class="toggle toggle-warning" />
                        </div>
                    </div>
                </div>

                <!-- PURCHASE FIRE MODE -->
                <div class="card bg-base-100 border border-base-300 shadow-sm">
                    <div class="card-body">
                        <h2 class="card-title text-base">🛒 Purchase Event Trigger Mode</h2>
                        <p class="text-sm text-gray-500 mb-4">Choose when the Purchase conversion event should automatically fire to Meta.</p>

                        <div class="space-y-3">
                            <label
                                v-for="mode in fireModes"
                                :key="mode.value"
                                class="flex items-start gap-3 p-3 rounded-lg border cursor-pointer transition-colors"
                                :class="form.purchase_fire_mode === mode.value
                                    ? 'border-primary bg-primary/5'
                                    : 'border-base-300 hover:border-gray-400'"
                            >
                                <input
                                    v-model="form.purchase_fire_mode"
                                    :value="mode.value"
                                    type="radio"
                                    class="radio radio-primary mt-0.5"
                                />
                                <div>
                                    <p class="font-medium text-sm">{{ mode.label }}</p>
                                    <p class="text-xs text-gray-400">{{ mode.description }}</p>
                                </div>
                            </label>
                        </div>
                    </div>
                </div>

                <!-- ACTIONS -->
                <div class="flex gap-3 flex-wrap">
                    <button type="submit" class="btn btn-primary" :disabled="saving">
                        <span v-if="saving" class="loading loading-spinner loading-xs"></span>
                        {{ saving ? 'Saving...' : '💾 Save Settings' }}
                    </button>

                    <button
                        type="button"
                        @click="sendTestEvent"
                        :disabled="testing || !form.pixel_id || !form.access_token"
                        class="btn btn-outline btn-secondary"
                    >
                        <span v-if="testing" class="loading loading-spinner loading-xs"></span>
                        {{ testing ? 'Sending...' : '🧪 Send Test Event' }}
                    </button>
                </div>

                <div v-if="!form.pixel_id || !form.access_token" class="alert alert-warning text-sm">
                    ⚠️ Enter Pixel ID and Access Token before sending a test event.
                </div>
            </form>
        </div>
    </AdminLayout>
</template>
