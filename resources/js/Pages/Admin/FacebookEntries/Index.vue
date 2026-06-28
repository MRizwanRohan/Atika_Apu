<template>
    <Head title="Facebook Order Entry" />
    <AdminLayout>
        <div class="max-w-3xl mx-auto space-y-4 pb-10">
            <!-- Stats Bar -->
            <div class="rounded-2xl border border-gray-200 bg-white p-4 shadow-sm dark:border-gray-800 dark:bg-gray-900 flex flex-col gap-4">
                <div class="flex items-center justify-between">
                    <div class="flex items-center gap-3">
                        <div class="rounded-xl bg-gray-100 p-2 text-gray-700 dark:bg-gray-800 dark:text-gray-200">
                            <Package size="18" />
                        </div>
                        <div>
                            <p class="text-xs font-medium text-gray-500 uppercase tracking-wide">Performance</p>
                            <div class="flex items-center gap-1">
                                <select 
                                    v-model="period" 
                                    @change="fetchStats"
                                    class="bg-transparent border-none p-0 text-sm font-semibold text-gray-900 dark:text-white focus:ring-0 uppercase cursor-pointer"
                                >
                                    <option value="today">Today</option>
                                    <option value="this_month">This Month</option>
                                    <option value="last_2_months">Last 2 Months</option>
                                    <option value="custom">Custom Date</option>
                                </select>
                                <ChevronDown size="12" class="text-gray-400" />
                            </div>
                        </div>
                    </div>
                    
                    <Link 
                        :href="route('admin.facebook-entries.history')"
                        class="flex items-center gap-2 rounded-lg bg-gray-900 px-4 py-2 text-xs font-semibold uppercase tracking-wide text-white transition hover:bg-gray-800 dark:bg-white dark:text-gray-900 dark:hover:bg-gray-200"
                    >
                        <HistoryIcon size="14" />
                        HISTORY
                    </Link>
                </div>

                <div v-if="period === 'custom'" class="grid grid-cols-2 gap-3">
                    <div class="space-y-1">
                        <p class="pl-1 text-xs font-medium uppercase tracking-wide text-gray-500">From</p>
                        <input type="date" v-model="customDates.start" @change="fetchStats" class="h-10 w-full rounded-xl border border-gray-200 bg-white px-3 text-sm font-medium text-gray-900 dark:border-gray-700 dark:bg-gray-950 dark:text-white" />
                    </div>
                    <div class="space-y-1">
                        <p class="pl-1 text-xs font-medium uppercase tracking-wide text-gray-500">To</p>
                        <input type="date" v-model="customDates.end" @change="fetchStats" class="h-10 w-full rounded-xl border border-gray-200 bg-white px-3 text-sm font-medium text-gray-900 dark:border-gray-700 dark:bg-gray-950 dark:text-white" />
                    </div>
                </div>

                <div class="grid grid-cols-3 gap-2 border-t border-gray-100 pt-3 dark:border-gray-800">
                    <div class="text-center">
                        <p class="mb-1 text-xs font-medium uppercase tracking-wide text-gray-500">Total</p>
                        <p class="text-2xl font-semibold leading-none text-gray-900 dark:text-white">{{ stats.total }}</p>
                    </div>
                    <div class="text-center border-x border-gray-100 dark:border-gray-800">
                        <p class="mb-1 text-xs font-medium uppercase tracking-wide text-gray-500">Inside</p>
                        <p class="text-2xl font-semibold leading-none text-gray-900 dark:text-white">{{ stats.inside_dhaka }}</p>
                    </div>
                    <div class="text-center">
                        <p class="mb-1 text-xs font-medium uppercase tracking-wide text-gray-500">Outside</p>
                        <p class="text-2xl font-semibold leading-none text-gray-900 dark:text-white">{{ stats.outside_dhaka }}</p>
                    </div>
                </div>
            </div>

            <!-- Main Entry Area -->
            <div class="overflow-hidden rounded-2xl border border-gray-200 bg-white shadow-sm dark:border-gray-800 dark:bg-gray-900">
                <div class="flex border-b border-gray-100 bg-gray-50 p-1.5 dark:border-gray-800 dark:bg-gray-950">
                    <button 
                        @click="setArea('inside_dhaka')"
                        :class="form.area === 'inside_dhaka' ? 'bg-blue-600 text-white shadow-md' : 'text-gray-500 hover:text-gray-900 dark:text-gray-400 dark:hover:text-white'"
                        class="flex flex-1 items-center justify-center gap-2 rounded-xl py-3 text-sm font-medium transition-all"
                    >
                        <MapPin size="14" /> Inside Dhaka
                    </button>
                    <button 
                        @click="setArea('outside_dhaka')"
                        :class="form.area === 'outside_dhaka' ? 'bg-orange-600 text-white shadow-md' : 'text-gray-500 hover:text-gray-900 dark:text-gray-400 dark:hover:text-white'"
                        class="flex flex-1 items-center justify-center gap-2 rounded-xl py-3 text-sm font-medium transition-all"
                    >
                        <Truck size="14" /> Outside Dhaka
                    </button>
                </div>

                <div class="space-y-5 p-5 sm:p-6">
                    <div class="space-y-2">
                        <label class="px-1 text-xs font-medium uppercase tracking-wide text-gray-500">Entry Input</label>
                        <textarea
                            v-model="pasteInput"
                            @input="handlePaste"
                            placeholder="Paste messenger text here..."
                            class="h-32 w-full resize-none rounded-2xl border border-gray-200 bg-white p-4 text-base text-gray-900 transition focus:border-gray-400 focus:ring-0 dark:border-gray-700 dark:bg-gray-950 dark:text-white placeholder:text-gray-400"
                        ></textarea>
                    </div>

                    <!-- Bill Section -->
                    <div class="flex flex-col items-center justify-between gap-4 rounded-2xl border border-gray-200 bg-gray-50 p-5 dark:border-gray-800 dark:bg-gray-950 sm:flex-row">
                        <div class="text-center sm:text-left">
                            <p class="mb-1 text-xs font-medium uppercase tracking-wide text-gray-500">Collectible Amount</p>
                        </div>
                        <div class="relative w-full sm:w-44">
                            <span class="absolute inset-y-0 left-4 flex items-center text-lg font-semibold text-gray-500">৳</span>
                            <input 
                                v-model="form.bill_amount"
                                type="number"
                                class="h-14 w-full rounded-xl border border-gray-200 bg-white pl-10 pr-4 text-center text-2xl font-semibold text-gray-900 focus:border-gray-400 focus:ring-0 dark:border-gray-700 dark:bg-gray-900 dark:text-white sm:text-right"
                            />
                        </div>
                    </div>

                    <!-- Products List -->
                    <div v-if="selectedProducts.length > 0" class="space-y-3">
                        <div v-for="(item, idx) in selectedProducts" :key="idx" class="flex items-center justify-between rounded-2xl border border-gray-200 bg-white p-3 dark:border-gray-800 dark:bg-gray-950">
                            <div class="flex items-center gap-3">
                                <div class="h-10 w-10 overflow-hidden rounded-xl border border-gray-200 bg-gray-50 dark:border-gray-800">
                                    <img :src="item.feature_image" class="w-full h-full object-cover" />
                                </div>
                                <div>
                                    <p class="max-w-[170px] line-clamp-1 text-sm font-medium text-gray-900 dark:text-white">{{ item.name }}</p>
                                    <p class="text-xs text-gray-500 dark:text-gray-400" v-if="item.variation_text">{{ item.variation_text }}</p>
                                </div>
                            </div>
                            <div class="flex items-center gap-4">
                                <div class="flex items-center rounded-lg border border-gray-200 bg-gray-50 px-2 py-1 dark:border-gray-800 dark:bg-gray-900">
                                    <button @click="updateQty(idx, -1)" class="p-1 text-gray-500 hover:text-gray-900 dark:hover:text-white"><Minus size="10"/></button>
                                    <span class="w-7 text-center text-sm font-semibold text-gray-900 dark:text-white">{{ item.quantity }}</span>
                                    <button @click="updateQty(idx, 1)" class="p-1 text-gray-500 hover:text-gray-900 dark:hover:text-white"><Plus size="10"/></button>
                                </div>
                                <button @click="removeProduct(idx)" class="flex h-8 w-8 items-center justify-center rounded-lg text-gray-400 transition-colors hover:bg-gray-100 hover:text-red-500 dark:hover:bg-gray-800"><Trash2 size="14"/></button>
                            </div>
                        </div>
                    </div>

                    <button 
                        @click="submitOrder"
                        :disabled="submitting || !form.bill_amount"
                        class="flex h-14 w-full items-center justify-center gap-3 rounded-2xl bg-gray-900 text-sm font-semibold uppercase tracking-wide text-white transition hover:bg-gray-800 active:scale-[0.99] disabled:opacity-40 dark:bg-white dark:text-gray-900 dark:hover:bg-gray-200"
                    >
                        <Send size="16" v-if="!submitting" />
                        <Loader2 size="16" class="animate-spin" v-else />
                        Send to Steadfast
                    </button>

                    <!-- <pre>{{ form }}</pre> -->

                    <!-- Flash Message Details -->
                    <div v-if="$page.props.flash.tracking_id" class="flex items-center justify-between rounded-2xl border border-emerald-200 bg-emerald-50 p-4 dark:border-emerald-900/40 dark:bg-emerald-950/20">
                        <div class="flex items-center gap-3">
                            <div class="rounded-xl bg-emerald-500 p-2 text-white">
                                <Check size="14" />
                            </div>
                            <div>
                                <p class="text-lg font-semibold uppercase tracking-wide text-emerald-700 dark:text-emerald-300">SID: {{ $page.props.flash.tracking_id }}</p>
                                <p class="text-sm text-emerald-700/80 dark:text-emerald-300">Consignment Success</p>
                            </div>
                        </div>
                        <div class="text-right">
                            <p class="mb-1 text-xs font-medium uppercase tracking-wide text-gray-500">Final Amount</p>
                            <p class="text-lg font-semibold text-emerald-700 dark:text-emerald-300">৳{{ $page.props.flash.cod_amount }}</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Product Explorer -->
            <div class="space-y-3 rounded-2xl border border-gray-200 bg-white p-4 shadow-sm dark:border-gray-800 dark:bg-gray-900">
                <div class="px-1">
                    <label class="text-xs font-medium uppercase tracking-wide text-gray-500">Product Search (Optional)</label>
                </div>
                <div class="flex items-center gap-3 rounded-xl border border-gray-200 bg-gray-50 px-4 py-3 dark:border-gray-800 dark:bg-gray-950">
                    <Search size="16" class="text-gray-400" />
                    <input 
                        v-model="productSearch"
                        @input="handleProductSearch"
                        placeholder="Quick search products..."
                        class="flex-1 border-none bg-transparent text-sm font-medium text-gray-900 focus:ring-0 placeholder:text-gray-400 dark:text-white"
                    />
                </div>
                
                <div v-if="searchResults.length > 0" class="grid grid-cols-1 gap-2">
                    <div v-for="product in searchResults" :key="product.id" class="group flex items-center justify-between rounded-xl border border-gray-200 p-3 transition-all dark:border-gray-800">
                        <div class="flex items-center gap-3">
                            <div class="h-12 w-12 overflow-hidden rounded-xl border border-gray-200 bg-gray-100 dark:border-gray-800 dark:bg-gray-900">
                                <img :src="product.feature_image" class="w-full h-full object-cover" />
                            </div>
                            <div>
                                <p class="max-w-[220px] line-clamp-1 text-sm font-medium text-gray-900 dark:text-white">{{ product.name }}</p>
                                <p class="mt-0.5 text-xs text-gray-500 dark:text-gray-400">{{ product.product_code }} - ৳{{ product.price }}</p>
                            </div>
                        </div>
                        
                        <div class="flex gap-1.5" v-if="product.type === 'variable'">
                            <button 
                                v-for="variant in product.variations" 
                                :key="variant.id"
                                @click="addProduct(product, variant)"
                                class="rounded-xl border border-gray-200 bg-white px-3 py-2 text-xs font-medium text-gray-700 transition hover:bg-gray-900 hover:text-white dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 dark:hover:bg-white dark:hover:text-gray-900"
                            >
                                {{ variant.attributes.map(a => a.value?.value).join('/') }}
                            </button>
                        </div>
                        <button 
                            v-else
                            @click="addProduct(product)"
                            class="rounded-xl bg-gray-900 px-4 py-2 text-xs font-semibold text-white transition hover:bg-gray-800 dark:bg-white dark:text-gray-900 dark:hover:bg-gray-200"
                        >
                            ADD ITEM
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </AdminLayout>
</template>

<script setup>
import { ref, onMounted } from 'vue';
import { Head, Link, router } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import { 
    Package, MapPin, Truck, History as HistoryIcon, Search, Plus, Minus, Trash2, Send, Loader2, Check, ChevronDown
} from 'lucide-vue-next';
import axios from 'axios';
import { toast } from "@steveyuowo/vue-hot-toast";

// Form State
const pasteInput = ref('');
const form = ref({
    customer_name: '',
    customer_phone: '',
    customer_address: '',
    bill_amount: 0,
    shipping_cost: 120,
    area: 'outside_dhaka',
    items: []
});

const convertBanglaToEnglish = (str) => {
    if (!str) return '';
    const banglaDigits = {'০': '0', '১': '1', '২': '2', '৩': '3', '৪': '4', '৫': '5', '৬': '6', '৭': '7', '৮': '8', '৯': '9'};
    return str.toString().replace(/[০-৯]/g, d => banglaDigits[d]);
};

const stats = ref({ total: 0, inside_dhaka: 0, outside_dhaka: 0 });
const selectedProducts = ref([]);
const productSearch = ref('');
const searchResults = ref([]);
const submitting = ref(false);
const backupBill = ref(0); // Store the parsed bill amount

// Methods
const setArea = (area) => {
    form.value.area = area;
    form.value.shipping_cost = area === 'inside_dhaka' ? 60 : 120;
};

const handlePaste = () => {
    let text = pasteInput.value || '';
    
    // Convert Bangla digits to English digits
    text = convertBanglaToEnglish(text);
    
    const lines = text.split('\n');
    const cleanLines = lines.map(l => l.trim()).filter(l => l);
    
    // Reset but keep area/shipping
    form.value.customer_name = '';
    form.value.customer_phone = '';
    form.value.customer_address = '';
    form.value.bill_amount = 0;

    let phoneLineIndex = -1;
    let district = '';
    let thana = '';

    cleanLines.forEach((line, idx) => {
        // 1. Parse Phone
        const phoneMatch = line.match(/01[3-9]\d{8}/);
        if (phoneMatch && !form.value.customer_phone) {
            form.value.customer_phone = phoneMatch[0];
            phoneLineIndex = idx;
        }

        // 2. Parse Bill (Look for labels)
        const billMatch = line.match(/(?:bill|due|amount|total|tk|taka|বিল|টাকা|এমাউন্ট)[\s:=—–-]*(\d+)/i);
        if (billMatch) {
            form.value.bill_amount = parseInt(billMatch[1]);
            backupBill.value = form.value.bill_amount;
        }

        // 3. Parse Name
        const nameMatch = line.match(/^(?:name|নাম)[\s:=—–-]*(.*)/i);
        if (nameMatch) {
            form.value.customer_name = nameMatch[1].trim();
        }

        // 4. Parse Address (Explicit)
        const addrMatch = line.match(/^(?:address|location|ঠিকানা|এড্রেস)[\s:=—–-]*(.*)/i);
        if (addrMatch) {
            form.value.customer_address = addrMatch[1].trim();
        }

        // 5. Parse District
        const distMatch = line.match(/^(?:district|dist|jela|জেলা)[\s:=—–-]*(.*)/i);
        if (distMatch) {
            district = distMatch[1].trim();
        }

        // 6. Parse Thana
        const thanaMatch = line.match(/^(?:thana|upazila|upazilla|থানা|উপজেলা)[\s:=—–-]*(.*)/i);
        if (thanaMatch) {
            thana = thanaMatch[1].trim();
        }
    });

    // Fallbacks
    if (!form.value.customer_name && cleanLines.length > 0) {
        const firstLine = cleanLines[0];
        if (firstLine.length < 30 && !firstLine.includes(':') && !firstLine.match(/\d/)) {
            form.value.customer_name = firstLine;
        }
    }

    // Address Fallback: If no explicit address, take line AFTER phone
    if (!form.value.customer_address && phoneLineIndex !== -1 && phoneLineIndex < cleanLines.length - 1) {
        const nextLine = cleanLines[phoneLineIndex + 1];
        // Ensure next line is not another labeled field
        if (!nextLine.match(/^(?:bill|due|amount|total|tk|taka|বিল|টাকা|এমাউন্ট|name|নাম|district|dist|jela|জেলা|thana|upazila|upazilla|থানা|উপজেলা)/i)) {
            form.value.customer_address = nextLine;
        }
    }

    // Secondary Address Fallback (Original logic)
    if (!form.value.customer_address) {
        for (let line of cleanLines) {
            if (line.length > 10 && 
                !line.match(/01[3-9]\d{8}/) && 
                !line.match(/(?:bill|due|amount|total|tk|taka|বিল|টাকা|এমাউন্ট)/i) &&
                !line.match(/^(?:name|নাম|address|location|ঠিকানা|এড্রেস|district|dist|jela|জেলা|thana|upazila|upazilla|থানা|উপজেলা)/i)
            ) {
                form.value.customer_address = line;
                break;
            }
        }
    }

    // Combine District/Thana into Address
    let finalAddrParts = [];
    if (form.value.customer_address) finalAddrParts.push(form.value.customer_address);
    if (thana && !form.value.customer_address.toLowerCase().includes(thana.toLowerCase())) finalAddrParts.push(thana);
    if (district && !form.value.customer_address.toLowerCase().includes(district.toLowerCase())) finalAddrParts.push(district);
    
    form.value.customer_address = finalAddrParts.join(', ');

    // Product Code Search
    let extractedCode = '';
    cleanLines.forEach(line => {
        const codeMatch = line.match(/(?:code|কোড|id|sku|item)[\s:]*([A-Za-z0-9-]+)/i);
        if (codeMatch) {
            extractedCode = codeMatch[1].trim();
        }
    });

    if (extractedCode) {
        productSearch.value = extractedCode;
        handleProductSearch();
    }
};

const handleProductSearch = async () => {
    if (productSearch.value.length < 2) {
        searchResults.value = [];
        return;
    }
    try {
        const res = await axios.get(route('admin.facebook-entries.search-products'), {
            params: { search: productSearch.value }
        });
        searchResults.value = res.data;
    } catch (e) {}
};

const addProduct = (product, variation = null) => {
    const item = {
        id: product.id,
        variation_id: variation ? variation.id : null,
        name: product.name,
        price: variation ? variation.price : product.price,
        feature_image: product.feature_image,
        quantity: 1,
        variation_text: variation ? variation.attributes.map(a => a.value?.value).join('/') : ''
    };
    if (selectedProducts.value.length === 0) backupBill.value = form.value.bill_amount;
    selectedProducts.value.push(item);
    form.value.bill_amount = parseFloat(item.price);
    productSearch.value = '';
    searchResults.value = [];
};

const removeProduct = (idx) => {
    selectedProducts.value.splice(idx, 1);
    if (selectedProducts.value.length === 0) {
        form.value.bill_amount = backupBill.value;
    } else {
        form.value.bill_amount = selectedProducts.value.reduce((sum, item) => sum + (item.price * item.quantity), 0);
    }
};

const updateQty = (idx, delta) => {
    const item = selectedProducts.value[idx];
    const oldQty = item.quantity;
    item.quantity = Math.max(1, item.quantity + delta);
    if (item.quantity !== oldQty) {
        form.value.bill_amount = parseFloat(form.value.bill_amount) + (delta * item.price);
    }
};

const submitOrder = () => {
    submitting.value = true;
    
    // Convert Bangla digits to English digits for fields
    form.value.customer_phone = convertBanglaToEnglish(form.value.customer_phone);
    form.value.bill_amount = convertBanglaToEnglish(form.value.bill_amount);

    form.value.items = selectedProducts.value.map(i => ({
        id: i.id,
        variation_id: i.variation_id,
        quantity: i.quantity
    }));

    router.post(route('admin.facebook-entries.store'), form.value, {
        onSuccess: () => {
            toast.success('Facebook order entry saved successfully');
            resetForm();
            fetchStats();
        },
        onError: () => toast.error('Failed to save entry. Check values.'),
        onFinish: () => submitting.value = false
    });
};

const resetForm = () => {
    form.value = {
        customer_name: '',
        customer_phone: '',
        customer_address: '',
        bill_amount: 0,
        shipping_cost: form.value.area === 'inside_dhaka' ? 60 : 120,
        area: form.value.area,
        items: []
    };
    pasteInput.value = '';
    selectedProducts.value = [];
};

const period = ref('today');
const customDates = ref({ start: '', end: '' });

const fetchStats = async () => {
    try {
        const res = await axios.get(route('admin.facebook-entries.stats'), {
            params: {
                period: period.value,
                start_date: customDates.value.start,
                end_date: customDates.value.end
            }
        });
        stats.value = res.data;
    } catch (e) {}
};

const formatDate = (date) => {
    return new Date(date).toLocaleString('en-US', {
        month: 'short', day: 'numeric', hour: '2-digit', minute: '2-digit'
    });
};

onMounted(() => fetchStats());
</script>

<style scoped>
input::-webkit-outer-spin-button, input::-webkit-inner-spin-button {
    -webkit-appearance: none;
    appearance: none;
    margin: 0;
}
input[type=number] { 
    -moz-appearance: textfield;
    appearance: textfield;
}
</style>
