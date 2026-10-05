<script setup>
import { computed, onMounted } from 'vue';
import { Head, Link, usePage } from '@inertiajs/vue3';
import FrontendLayout from '@/Layouts/FrontendLayout.vue';
import { useMetaPixel } from '@/Composables/useMetaPixel';
import { jsPDF } from 'jspdf';
import autoTable from 'jspdf-autotable';

const props = defineProps({
    order: {
        type: Object,
        required: true
    },
    // Server-generated event_id for Pixel+CAPI deduplication
    meta_event_id: {
        type: String,
        default: null
    },
    // Only true when purchase_fire_mode === 'on_order_created'
    fire_pixel_now: {
        type: Boolean,
        default: false
    }
});

const page = usePage();
const isAuthenticated = computed(() => !!page.props.auth?.user);

const { trackPurchase } = useMetaPixel();

// Fire Purchase Pixel only when mode is on_order_created.
// For on_confirmed / on_delivered, CAPI handles it server-side when admin acts.
onMounted(() => {
    if (props.fire_pixel_now) {
        trackPurchase(props.order, props.meta_event_id);
    }
});

// Format date
const formatDate = (dateString) => {
    if (!dateString) return '';
    const date = new Date(dateString);
    return date.toLocaleDateString('en-US', {
        year: 'numeric',
        month: 'long',
        day: 'numeric',
        hour: '2-digit',
        minute: '2-digit'
    });
};

// Calculate estimated delivery (3-5 business days)
const estimatedDelivery = computed(() => {
    const today = new Date();
    const minDays = 3;
    const maxDays = 5;
    
    const minDate = new Date(today);
    minDate.setDate(today.getDate() + minDays);
    
    const maxDate = new Date(today);
    maxDate.setDate(today.getDate() + maxDays);
    
    const options = { month: 'short', day: 'numeric' };
    return `${minDate.toLocaleDateString('en-US', options)} - ${maxDate.toLocaleDateString('en-US', options)}`;
});

const downloadInvoice = () => {
    const doc = new jsPDF();
    const currency = 'Tk. ';
    const primaryColor = [17, 24, 39]; // Gray 900
    const secondaryColor = [107, 114, 128]; // Gray 500
    const accentColor = [13, 148, 136]; // Teal 600 (adjust to match your brand primary if needed)
    
    // Top Accent Bar
    doc.setFillColor(...primaryColor);
    doc.rect(0, 0, 210, 40, 'F');
    
    // Header - Title
    doc.setFontSize(24);
    doc.setTextColor(255, 255, 255);
    doc.setFont('helvetica', 'bold');
    doc.text('INVOICE', 15, 25);
    
    doc.setFontSize(10);
    doc.setFont('helvetica', 'normal');
    doc.text(`#${props.order.order_number}`, 15, 32);
    
    // Branding in Header
    doc.setFontSize(20);
    doc.text('XYLO', 195, 25, { align: 'right' });
    doc.setFontSize(9);
    doc.text(window.location.hostname, 195, 32, { align: 'right' });
    
    // Body starts here
    let currentY = 55;
    
    // Status Badge
    const status = props.order.payment_status?.toUpperCase() || 'UNPAID';
    doc.setFontSize(8);
    doc.setDrawColor(...secondaryColor);
    doc.setFillColor(249, 250, 251);
    doc.roundedRect(15, currentY - 5, 25, 6, 1, 1, 'FD');
    doc.setTextColor(...primaryColor);
    doc.setFont('helvetica', 'bold');
    doc.text(status, 27.5, currentY - 0.5, { align: 'center' });
    
    currentY += 15;
    
    // Section Boxes
    // Left: Bill To
    doc.setFontSize(9);
    doc.setTextColor(...secondaryColor);
    doc.setFont('helvetica', 'bold');
    doc.text('BILL TO', 15, currentY);
    
    doc.setFontSize(11);
    doc.setTextColor(...primaryColor);
    doc.text(props.order.customer_name || 'Customer', 15, currentY + 7);
    
    doc.setFontSize(9);
    doc.setTextColor(...secondaryColor);
    doc.setFont('helvetica', 'normal');
    const splitAddress = doc.splitTextToSize(props.order.shipping_address || '', 70);
    doc.text(splitAddress, 15, currentY + 13);
    const addressHeight = splitAddress.length * 5;
    doc.text(`Phone: ${props.order.customer_phone || ''}`, 15, currentY + 15 + addressHeight);
    
    // Right: Order Info
    const rightSideX = 130;
    doc.setFontSize(9);
    doc.setTextColor(...secondaryColor);
    doc.setFont('helvetica', 'bold');
    doc.text('INVOICE DETAILS', rightSideX, currentY);
    
    doc.setFontSize(9);
    doc.setTextColor(...primaryColor);
    doc.setFont('helvetica', 'normal');
    
    const details = [
        ['Date:', formatDate(props.order.created_at)],
        ['Payment:', props.order.payment_method === 'cod' ? 'Cash on Delivery' : props.order.payment_method?.toUpperCase()],
        ['Currency:', 'BDT (Tk.)']
    ];
    
    details.forEach((item, index) => {
        doc.setTextColor(...secondaryColor);
        doc.text(item[0], rightSideX, currentY + 7 + (index * 6));
        doc.setTextColor(...primaryColor);
        doc.text(item[1], 195, currentY + 7 + (index * 6), { align: 'right' });
    });
    
    currentY = Math.max(currentY + 25 + addressHeight, currentY + 35);
    
    // Items Table
    const tableData = props.order.items.map((item, index) => [
        String(index + 1).padStart(2, '0'),
        item.product?.name + (item.product_variation?.attributes?.map(a => ` (${a.value?.value})`).join('') || ''),
        item.quantity,
        `${parseFloat(item.subtotal / (item.quantity || 1)).toFixed(2)}`,
        `${parseFloat(item.subtotal || 0).toFixed(2)}`
    ]);
    
    autoTable(doc, {
        startY: currentY,
        head: [['#', 'PRODUCT DESCRIPTION', 'QTY', 'UNIT PRICE', 'AMOUNT']],
        body: tableData,
        theme: 'striped',
        headStyles: { 
            fillColor: primaryColor, 
            textColor: [255, 255, 255], 
            fontSize: 8, 
            fontStyle: 'bold',
            halign: 'left',
            cellPadding: 4
        },
        bodyStyles: { 
            fontSize: 9, 
            textColor: primaryColor,
            cellPadding: 4
        },
        columnStyles: {
            0: { halign: 'center', cellWidth: 10 },
            1: { halign: 'left' },
            2: { halign: 'center', cellWidth: 20 },
            3: { halign: 'right', cellWidth: 30 },
            4: { halign: 'right', cellWidth: 30 }
        },
        margin: { left: 15, right: 15 }
    });
    
    currentY = doc.lastAutoTable.finalY + 15;
    
    // Summary Section
    const summaryX = 130;
    const valueX = 195;
    
    const summaryLines = [
        ['Subtotal', `${currency}${parseFloat(props.order.subtotal || 0).toFixed(2)}`],
        ['Shipping', `${currency}${parseFloat(props.order.shipping_cost || 0).toFixed(2)}`]
    ];
    
    if (parseFloat(props.order.discount_total || props.order.discount || 0) > 0) {
        summaryLines.push(['Discount', `-${currency}${parseFloat(props.order.discount_total || props.order.discount || 0).toFixed(2)}`]);
    }
    
    summaryLines.forEach((line, index) => {
        doc.setFontSize(9);
        doc.setTextColor(...secondaryColor);
        doc.text(line[0], summaryX, currentY + (index * 7));
        doc.setTextColor(...primaryColor);
        doc.text(line[1], valueX, currentY + (index * 7), { align: 'right' });
    });
    
    currentY += (summaryLines.length * 7) + 5;
    
    // Total Line
    doc.setFillColor(249, 250, 251);
    doc.rect(summaryX - 5, currentY - 5, (valueX - summaryX) + 10, 12, 'F');
    
    doc.setFontSize(11);
    doc.setTextColor(...primaryColor);
    doc.setFont('helvetica', 'bold');
    doc.text('TOTAL AMOUNT', summaryX, currentY + 3);
    doc.setFontSize(13);
    doc.text(`${currency}${parseFloat(props.order.total || 0).toFixed(2)}`, valueX, currentY + 3, { align: 'right' });
    
    // Footer
    const pageHeight = doc.internal.pageSize.height;
    
    // Payment Notice
    doc.setFontSize(8);
    doc.setTextColor(...secondaryColor);
    doc.setFont('helvetica', 'bold');
    doc.text('PAYMENT INFORMATION', 15, pageHeight - 35);
    doc.setFont('helvetica', 'normal');
    doc.text('Please keep this invoice for your records.', 15, pageHeight - 30);
    doc.text('For any queries, contact our support team.', 15, pageHeight - 26);
    
    // Bottom Bar
    doc.setFillColor(...primaryColor);
    doc.rect(0, pageHeight - 15, 210, 15, 'F');
    
    doc.setFontSize(9);
    doc.setTextColor(255, 255, 255);
    doc.setFont('helvetica', 'italic');
    doc.text('Thank you for choosing XYLO. Your satisfaction is our priority.', 105, pageHeight - 6, { align: 'center' });
    
    doc.save(`Invoice-${props.order.order_number}.pdf`);
};
</script>

<template>
    <Head title="Order Confirmed" />
    <FrontendLayout>
        <div class="min-h-screen bg-white py-12 px-4 sm:px-6 lg:px-8">
            <div class="max-w-4xl mx-auto">
                <!-- Success Header -->
                <div class="text-center mb-12">
                    <!-- <div class="inline-flex items-center justify-center w-20 h-20 bg-green-50 rounded-full mb-6">
                        <svg class="w-10 h-10 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" class="checkmark-path" />
                        </svg>
                    </div> -->
                    <h1 class="text-4xl font-light text-gray-900 tracking-tight mb-4">
                        Thank you for your order
                    </h1>
                    <p class="text-lg text-gray-500 font-light max-w-xl mx-auto">
                        Your order has been placed successfully. We've sent a confirmation email to your registered address.
                    </p>
                </div>

                <div class="grid grid-cols-1 lg:grid-cols-5 gap-12">
                    <!-- Order Items & Details -->
                    <div class="lg:col-span-3 space-y-10">
                        <!-- Items Section -->
                        <section>
                            <h2 class="text-xs uppercase tracking-widest text-gray-400 font-semibold mb-6">Your Items</h2>
                            <div class="divide-y divide-gray-100">
                                <div 
                                    v-for="item in order.items" 
                                    :key="item.id"
                                    class="py-6 first:pt-0 last:pb-0 flex gap-6"
                                >
                                    <div class="w-24 h-24 bg-gray-50 rounded-lg overflow-hidden flex-shrink-0">
                                        <img 
                                            v-if="item.product?.feature_image"
                                            :src="item.product.feature_image"
                                            :alt="item.product?.name"
                                            class="w-full h-full object-cover"
                                        />
                                        <div v-else class="w-full h-full flex items-center justify-center text-gray-200">
                                            <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" />
                                            </svg>
                                        </div>
                                    </div>
                                    <div class="flex-1 flex flex-col justify-center">
                                        <h3 class="text-base font-medium text-gray-900 mb-1">{{ item.product?.name }}</h3>
                                        <div v-if="item.product_variation?.attributes?.length" class="text-sm text-gray-400 mb-1 space-x-2">
                                            <span v-for="(attr, index) in item.product_variation.attributes" :key="attr.id">
                                                {{ attr.value?.value }}
                                                <span v-if="index < item.product_variation.attributes.length - 1" class="ml-2">|</span>
                                            </span>
                                        </div>
                                        <div class="flex justify-between items-center mt-2">
                                            <span class="text-sm text-gray-500 font-light">Qty {{ item.quantity }}</span>
                                            <span class="text-sm font-medium text-gray-900">৳{{ parseFloat(item.subtotal || 0).toFixed(2) }}</span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </section>

                        <!-- Delivery Status -->
                        <section class="bg-gray-50/50 rounded-2xl p-6 border border-gray-100 flex items-center gap-6">
                            <div class="w-12 h-12 bg-white rounded-full flex items-center justify-center shadow-sm">
                                <svg class="w-6 h-6 text-primary" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4" />
                                </svg>
                            </div>
                            <div>
                                <h3 class="text-sm font-semibold text-gray-900">Order Placed Successfully</h3>
                                <p class="text-sm text-gray-500 font-light">Estimated delivery: <span class="text-primary font-medium">{{ estimatedDelivery }}</span></p>
                            </div>
                        </section>
                    </div>

                    <!-- Order Sidebar -->
                    <div class="lg:col-span-2 space-y-8">
                        <!-- Brief Summary -->
                        <div class="bg-gray-50 rounded-2xl p-8 sticky top-8">
                            <h2 class="text-xs uppercase tracking-widest text-gray-400 font-semibold mb-6">Order Summary</h2>
                            
                            <div class="space-y-4 mb-8">
                                <div class="flex justify-between text-sm">
                                    <span class="text-gray-500">Order Number</span>
                                    <span class="font-medium text-gray-900">{{ order.order_number }}</span>
                                </div>
                                <div class="flex justify-between text-sm">
                                    <span class="text-gray-500">Subtotal</span>
                                    <span class="text-gray-900 font-medium">৳{{ parseFloat(order.subtotal || 0).toFixed(2) }}</span>
                                </div>
                                <div class="flex justify-between text-sm">
                                    <span class="text-gray-500">Delivery</span>
                                    <span class="text-gray-900 font-medium">৳{{ parseFloat(order.shipping_cost || 0).toFixed(2) }}</span>
                                </div>
                                <p v-if="Number(order.global_discount_total) > 0" class="text-xs text-green-700">Automatic offer saving: ৳{{ Number(order.global_discount_total).toFixed(2) }} (included in discount)</p>
                                <p v-if="Number(order.shipping_discount_total) > 0" class="text-xs text-green-700">Free delivery saving: ৳{{ Number(order.shipping_discount_total).toFixed(2) }}</p>
                                <div v-if="parseFloat(order.discount_total || order.discount || 0) > 0" class="flex justify-between text-sm">
                                    <span class="text-green-600 font-medium">Discount</span>
                                    <span class="font-medium text-green-600">-৳{{ parseFloat(order.discount_total || order.discount || 0).toFixed(2) }}</span>
                                </div>
                                <div class="flex justify-between text-xl font-bold border-t border-gray-200 pt-6 mt-6">
                                    <span class="text-gray-900">Total</span>
                                    <span class="text-primary">৳{{ parseFloat(order.total || 0).toFixed(2) }}</span>
                                </div>
                            </div>

                            <div class="space-y-6 pt-6 border-t border-gray-200">
                                <div>
                                    <h3 class="text-xs uppercase tracking-wider text-gray-400 font-bold mb-2">Shipping to</h3>
                                    <p class="text-sm text-gray-600 font-light leading-relaxed">
                                        <span class="font-semibold text-gray-900 block mb-1">{{ order.customer_name }}</span>
                                        {{ order.shipping_address }}<br>
                                        {{ order.customer_phone }}
                                    </p>
                                </div>
                                <div>
                                    <h3 class="text-xs uppercase tracking-wider text-gray-400 font-bold mb-2">Payment</h3>
                                    <p class="text-sm text-gray-600 font-light">
                                        {{ order.payment_method === 'cod' ? 'Cash on Delivery' : order.payment_method }}
                                        <span class="block text-xs uppercase tracking-wider text-amber-600 font-bold mt-1">
                                            {{ order.payment_status === 'unpaid' ? 'Pending Payment' : 'Paid' }}
                                        </span>
                                    </p>
                                </div>
                            </div>

                            <div class="mt-10 flex flex-col gap-3">
                                <Link 
                                    href="/"
                                    class="w-full py-4 px-6 bg-gray-900 text-white rounded-xl text-center font-medium hover:bg-gray-800 transition-all shadow-lg hover:shadow-xl"
                                >
                                    Continue Shopping
                                </Link>
                                <Link 
                                    v-if="isAuthenticated"
                                    :href="route('user.dashboard')"
                                    class="w-full py-4 px-6 bg-white border border-gray-200 text-gray-900 rounded-xl text-center font-medium hover:bg-gray-50 transition-all"
                                >
                                    View Orders
                                </Link>
                                <button 
                                    @click="downloadInvoice"
                                    class="w-full py-4 px-6 bg-primary/10 text-primary rounded-xl text-center font-medium hover:bg-primary/20 transition-all flex items-center justify-center gap-2 group border border-primary/20"
                                >
                                    <svg class="w-5 h-5 transition-transform group-hover:-translate-y-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4 16v1a2 2 0 002 2h12a2 2 0 002-2v-1m-4-4l-4 4m0 0l-4-4m4 4V4" />
                                    </svg>
                                    Download Invoice
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </FrontendLayout>
</template>

<style scoped>
.checkmark-path {
    stroke-dasharray: 100;
    stroke-dashoffset: 100;
    animation: dash 1.2s cubic-bezier(0.4, 0, 0.2, 1) forwards;
}

@keyframes dash {
    to {
        stroke-dashoffset: 0;
    }
}
</style>
