<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Invoice - {{ $order->order_number }}</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;500;600;700&display=swap" rel="stylesheet">
    
    <!-- Lucide icons from CDN for premium look -->
    <script src="https://unpkg.com/lucide@latest"></script>

    <!-- Tailwind CSS CDN for the header styling -->
    <script src="https://cdn.tailwindcss.com"></script>

    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        @page {
            margin: 0;
            size: A4 portrait;
        }

        body {
            font-family: "Montserrat", sans-serif;
            background-color: #f3f4f6;
            margin: 0;
            padding-top: 80px; /* Spacer for fixed header */
        }

        .invoice-card-wrapper {
            display: flex;
            justify-content: center;
            align-items: center;
            padding: 20px;
        }

        .invoice-container {
            max-width: 148mm;
            background: white;
            border: 1px solid #e0e0e0;
            width: 100%;
            box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.05), 0 4px 6px -2px rgba(0, 0, 0, 0.05);
        }

        .invoice-content {
            padding: 6mm;
        }

        /* Header Section */
        .invoice-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 8mm;
            padding-bottom: 5mm;
            border-bottom: 2px solid #000;
        }

        .company-logo {
            flex: 0 0 auto;
        }

        .company-logo img {
            max-height: 50px;
            width: auto;
        }

        .company-details {
            flex: 1;
            text-align: center;
            padding: 0 10px;
        }

        .company-name {
            font-size: 18px;
            font-weight: 700;
            color: #000;
            margin-bottom: 3px;
        }

        .company-address {
            font-size: 11px;
            color: #333;
            line-height: 1.4;
        }

        .invoice-title-box {
            flex: 0 0 auto;
            text-align: right;
        }

        .invoice-title {
            font-size: 16px;
            font-weight: 700;
            color: #000;
            margin-bottom: 4px;
        }

        .invoice-number {
            font-size: 10px;
            color: #000;
            margin-bottom: 2px;
            text-align: center;
        }

        /* Customer and Order Info */
        .info-section {
            display: flex;
            justify-content: space-between;
            margin-bottom: 6mm;
        }

        .customer-info,
        .order-info {
            flex: 1;
        }

        .info-title {
            font-size: 10px;
            font-weight: 600;
            color: #000;
            margin-bottom: 3px;
            text-transform: uppercase;
        }

        .info-content {
            font-size: 11px;
            color: #333;
            line-height: 1.5;
        }

        .info-content div {
            margin-bottom: 2px;
        }

        .order-info {
            text-align: right;
        }

        /* Items Table */
        .items-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 5mm;
            font-size: 11px;
        }

        .items-table thead {
            background-color: #f0f0f0;
            border-top: 1px solid #333;
            border-bottom: 1px solid #333;
        }

        .items-table th {
            padding: 6px 8px;
            text-align: left;
            font-weight: 600;
            color: #000;
            font-size: 11px;
        }

        .items-table th:last-child,
        .items-table td:last-child {
            text-align: right;
        }

        .items-table td {
            padding: 6px 8px;
            color: #333;
            border-bottom: 1px solid #e0e0e0;
        }

        .items-table tbody tr:last-child td {
            border-bottom: 1px solid #333;
        }

        .item-image {
            width: 30px;
            height: 30px;
            object-fit: cover;
            border: 1px solid #ddd;
            display: block;
        }

        /* Totals Section */
        .totals-section {
            margin-bottom: 5mm;
            display: flex;
            flex-direction: column;
            align-items: flex-end;
        }

        .totals-row {
            display: flex;
            justify-content: space-between;
            padding: 2px 0;
            font-size: 11px;
            color: #333;
            min-width: 200px;
        }

        .totals-row .label {
            text-align: left;
            padding-right: 20px;
        }

        .totals-row .value {
            text-align: right;
        }

        .totals-row.total {
            font-weight: 700;
            font-size: 11px;
            color: #000;
            border-top: 1px solid #333;
            padding-top: 4px;
            margin-top: 2px;
        }

        /* Footer Section */
        .footer-info {
            display: flex;
            justify-content: space-between;
            font-size: 10px;
            color: #555;
            padding-top: 5mm;
            border-top: 1px solid #ddd;
        }

        .footer-item {
            flex: 1;
        }

        .bottom-section {
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        @media print {
            body {
                background: white !important;
                padding: 0 !important;
                margin: 0 !important;
            }

            .no-print {
                display: none !important;
            }

            .invoice-card-wrapper {
                padding: 0 !important;
            }

            .invoice-container {
                box-shadow: none !important;
                border: none !important;
                max-width: 100% !important;
                width: 100% !important;
            }
        }
    </style>
</head>

<body>
    <!-- Premium Sticky Header Bar (non-printable) -->
    <header class="no-print fixed top-0 left-0 right-0 h-[60px] bg-white/95 border-b border-gray-200 shadow-sm backdrop-blur-md flex items-center justify-between px-6 z-50">
        <div class="flex items-center gap-3">
            <a href="{{ route('admin.orders.edit', $order->id) }}" class="flex items-center gap-1 text-xs font-bold text-gray-600 hover:text-gray-900 transition-colors bg-gray-100 hover:bg-gray-200 px-3 py-1.5 rounded-lg">
                <i data-lucide="arrow-left" class="w-3.5 h-3.5"></i>
                Back to Order
            </a>
        </div>
        
        <div class="flex items-center gap-2">
            <button onclick="window.print()" class="flex items-center gap-1.5 text-xs font-bold text-gray-700 hover:text-gray-900 transition-colors bg-gray-100 hover:bg-gray-200 px-3.5 py-1.5 rounded-lg">
                <i data-lucide="printer" class="w-4 h-4"></i>
                Print
            </button>
            <button id="download-btn" onclick="downloadPDF()" class="flex items-center gap-1.5 text-xs font-bold text-white transition-all bg-blue-600 hover:bg-blue-700 px-4 py-1.5 rounded-lg shadow-sm">
                <i data-lucide="download" class="w-4 h-4"></i>
                <span class="hidden sm:block">Download PDF</span>
            </button>
        </div>
    </header>

    <!-- Invoice Card Container -->
    <div class="invoice-card-wrapper">
        <div id="invoice-card" class="invoice-container">
            <div class="invoice-content">

                <!-- Customer and Order Info -->
                <div class="info-section">
                    <div class="customer-info">
                        <div class="info-title">Customer Info</div>
                        <div class="info-content">
                            <div><strong>Name:</strong> {{ $order->customer_name }}</div>
                            <div><strong>Phone:</strong> {{ $order->customer_phone }}</div>
                            <div><strong>Address:</strong> {{ $order->shipping_address }}</div>
                        </div>
                    </div>

                    @if ($order->consignment_id != null)
                        <div style="text-align: center; display: flex; flex-direction: column; justify-content: center; align-items: center;">
                            <span style="font-size: 13px; color: #666;">Parcel ID:</span>
                            <span style="font-size: 14px;"><strong>{{ $order->consignment_id }}</strong></span>
                        </div>
                    @endif

                    <div class="order-info">
                        @if ($logo)
                            <div class="company-logo" style="width: 100px; display: inline-block; vertical-align: top; margin-bottom: 5px;">
                                <img src="{{ asset($logo->logo) }}" alt="Logo" style="width: 100%;">
                            </div>
                        @endif
                        <div class="info-content">
                            <div>Invoice No: <strong>{{ $order->order_number }}</strong> </div>
                            <div>{{ $settings->address ?? 'N/A' }}</div>
                            <div>{{ $settings->store_email ?? 'N/A' }}</div>
                            <div>{{ $settings->store_phone_number ?? 'N/A' }}</div>
                        </div>
                    </div>
                </div>

                <!-- Items Table -->
                <table class="items-table">
                    <thead>
                        <tr>
                            <th>Image</th>
                            <th>Product Name</th>
                            <th>Attributes</th>
                            <th>Quantity</th>
                            <th>Price</th>
                            <th>Total</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($order->items as $item)
                            <tr>
                                <td>
                                    @if (isset($item->product->feature_image))
                                        <img src="{{ asset($item->product->feature_image) }}" alt="Product" class="item-image">
                                    @else
                                        <div style="width:30px;height:30px;background:#f0f0f0;border:1px solid #ddd;"></div>
                                    @endif
                                </td>
                                <td>{{ $item->product->name }}</td>
                                <td>
                                    @if ($item->productVariation && $item->productVariation->attributes && count($item->productVariation->attributes) > 0)
                                        @foreach ($item->productVariation->attributes as $index => $attr)
                                            {{ $attr->value->attribute->name }}: {{ $attr->value->value }}@if(!$loop->last), @endif
                                        @endforeach
                                    @else
                                        -
                                    @endif
                                </td>
                                <td>{{ $item->quantity }}</td>
                                <td>{{ number_format($item->unit_price, 0) }} Tk</td>
                                <td>{{ number_format($item->subtotal, 0) }} Tk</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>

                <div class="bottom-section">
                    <div>
                        @if ($order->customer_notes)
                            <div class="customer_notes">
                                <span style="font-size: 10px;"><strong>Customer Notes:</strong></span>
                                <span style="font-size: 10px;">{{ $order->customer_notes }}</span>
                            </div>
                        @endif
                    </div>
                    <!-- Totals -->
                    <div class="totals-section">
                        <div class="totals-row">
                            <div class="label">Total Qty:</div>
                            <div class="value">{{ $order->items->sum('quantity') }}</div>
                        </div>
                        <div class="totals-row">
                            <div class="label">Courier Charge:</div>
                            <div class="value">{{ number_format($order->shipping_cost, 0) }} Tk</div>
                        </div>
                        <div class="totals-row">
                            <div class="label">Total:</div>
                            <div class="value">{{ number_format($order->subtotal + $order->shipping_cost, 0) }} Tk</div>
                        </div>
                        <div class="totals-row">
                            <div class="label">Discount:</div>
                            <div class="value">{{ number_format($order->discount_total, 0) }} Tk</div>
                        </div>
                        @if(($order->global_discount_total ?? 0) > 0)
                        <div class="totals-row"><div class="label">Automatic offer (included):</div><div class="value">{{ number_format($order->global_discount_total, 2) }} Tk</div></div>
                        @endif
                        @if(($order->shipping_discount_total ?? 0) > 0)
                        <div class="totals-row"><div class="label">Free delivery saving:</div><div class="value">{{ number_format($order->shipping_discount_total, 2) }} Tk</div></div>
                        @endif
                        <div class="totals-row">
                            <div class="label">Paid:</div>
                            <div class="value">{{ number_format($order->paid_amount ?? 0, 0) }} Tk</div>
                        </div>
                        <div class="totals-row total">
                            <div class="label">Cash on Delivery:</div>
                            <div class="value">{{ number_format($order->total - ($order->paid_amount ?? 0), 0) }} Tk</div>
                        </div>
                    </div>
                </div>

                <!-- Footer -->
                <div class="footer-info">
                    <div class="footer-item">
                        <strong>Order Date:</strong> {{ date('Y-m-d', strtotime($order->created_at)) }},
                        <strong>Payment Method:</strong> {{ ucfirst(str_replace('_', ' ', $order->payment_method)) }}
                    </div>
                    <div class="footer-item" style="text-align: right;">
                        <strong>Source:</strong> {{ $order->source ?? 'Website' }},
                        <strong>Order Received By:</strong> {{ $order->received_by ?? 'Admin' }}
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- PDF Generation Libraries -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/html2canvas/1.4.1/html2canvas.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>

    <script>
        // Initialize Lucide Icons
        lucide.createIcons();

        // PDF Generation Script
        function downloadPDF() {
            const { jsPDF } = window.jspdf;
            const element = document.getElementById('invoice-card');
            const btn = document.getElementById('download-btn');
            
            // Set loading state
            const originalHTML = btn.innerHTML;
            btn.innerHTML = '<span class="inline-block animate-spin mr-1.5">⚡</span> Generating PDF...';
            btn.disabled = true;

            // Preload all images inside element to prevent cross-origin issues
            const images = element.getElementsByTagName('img');
            const promises = [];
            for (let i = 0; i < images.length; i++) {
                if (images[i].src && !images[i].complete) {
                    promises.push(new Promise((resolve) => {
                        images[i].onload = resolve;
                        images[i].onerror = resolve;
                    }));
                }
            }

            Promise.all(promises).then(() => {
                html2canvas(element, {
                    scale: 3, // High quality scale
                    useCORS: true,
                    allowTaint: false,
                    backgroundColor: '#ffffff',
                    logging: false
                }).then(canvas => {
                    const imgData = canvas.toDataURL('image/jpeg', 0.95);
                    
                    // Standard A4 sizes in mm: 210 x 297
                    const pdfWidth = 210;
                    const pdfHeight = 297;
                    
                    // We calculate aspect ratio based on our container's dimensions
                    const canvasWidth = canvas.width;
                    const canvasHeight = canvas.height;
                    const imgHeightInPdf = (canvasHeight * pdfWidth) / canvasWidth;
                    
                    const pdf = new jsPDF('p', 'mm', 'a4');
                    
                    // If invoice taller than standard A4 page, chunk it
                    let heightLeft = imgHeightInPdf;
                    let position = 0;
                    
                    pdf.addImage(imgData, 'JPEG', 0, position, pdfWidth, imgHeightInPdf);
                    heightLeft -= pdfHeight;
                    
                    while (heightLeft > 0) {
                        position = heightLeft - imgHeightInPdf;
                        pdf.addPage();
                        pdf.addImage(imgData, 'JPEG', 0, position, pdfWidth, imgHeightInPdf);
                        heightLeft -= pdfHeight;
                    }
                    
                    pdf.save('invoice-' + '{{ $order->order_number }}' + '.pdf');
                    
                    // Reset button state
                    btn.innerHTML = originalHTML;
                    btn.disabled = false;
                }).catch(err => {
                    console.error(err);
                    alert('PDF generation failed. Please try printing or reload the page.');
                    btn.innerHTML = originalHTML;
                    btn.disabled = false;
                });
            });
        }
    </script>
</body>

</html>
