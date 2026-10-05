<?php

namespace App\Services\Offers;

use App\Models\Cart;
use App\Models\DeliveryZone;
use App\Models\GeneralSetting;
use App\Models\Product;
use App\Models\ProductVariation;
use Illuminate\Validation\ValidationException;

class CheckoutPricingService
{
    public function unitPrice(Product $product, ?ProductVariation $variation = null): float
    {
        $price = (float) $product->price + (float) ($variation?->price ?? 0);
        $campaign = $product->campaigns->first(fn ($campaign) => $campaign->status === 'active'
            && now()->between(\Carbon\Carbon::parse($campaign->start_date), \Carbon\Carbon::parse($campaign->end_date)));

        return round(max(0, $campaign ? $campaign->calculateDiscountedPrice($price) : $price), 2);
    }

    public function refreshPrices(Cart $cart): void
    {
        $cart->loadMissing('items.product.campaigns', 'items.variation');
        foreach ($cart->items as $item) {
            if (! $item->product || ($item->product_variation_id && (! $item->variation || $item->variation->product_id !== $item->product_id))) {
                throw ValidationException::withMessages(['cart' => 'A cart product is no longer available. Please remove it.']);
            }
            // Keep resource evaluation read-only; price is resolved afresh for every quote.
            $item->price = $this->unitPrice($item->product, $item->variation);
        }
    }

    public function withShipping(array $pricing, Cart $cart, ?string $district, ?string $thana): array
    {
        $allFree = $cart->items->isNotEmpty() && $cart->items->every(fn ($item) => (bool) $item->product?->is_free_delivery);
        $known = (bool) $district || $allFree || $pricing['free_delivery'];
        $normal = $district ? $this->deliveryCharge($district, $thana) : null;
        $waived = $allFree || $pricing['free_delivery'];
        $payable = $waived ? 0 : $normal;

        return array_merge($pricing, [
            'normal_shipping_charge' => $normal,
            'shipping_discount' => $waived && $normal !== null ? $normal : 0,
            'shipping_payable' => $payable,
            'shipping_quote_status' => $known ? 'quoted' : 'address_required',
            'grand_total' => $payable !== null ? round($pricing['merchandise_payable'] + $payable, 2) : null,
        ]);
    }

    public function deliveryCharge(string $district, ?string $thana): float
    {
        $zone = $thana ? DeliveryZone::where('type', DeliveryZone::TYPE_THANA)->where('is_active', true)->where('name', $thana)
            ->whereHas('parent', fn ($q) => $q->where('type', DeliveryZone::TYPE_DISTRICT)->where('name', $district)->where('is_active', true))->first() : null;
        $zone ??= DeliveryZone::where('type', DeliveryZone::TYPE_DISTRICT)->where('is_active', true)->where('name', $district)->first();
        if ($zone && $zone->charge !== null) {
            return (float) $zone->charge;
        }
        $settings = GeneralSetting::first();

        return (float) ($district === 'Dhaka' ? ($settings?->shipping_charge_inside_dhaka ?? 60) : ($settings?->shipping_charge_outside_dhaka ?? 120));
    }

    /** Allocate exact paisa amounts to lines for stable totals and proportional refunds. */
    public function allocate(float $discount, array $lineTotals): array
    {
        $weights = array_map(fn ($amount) => max(0, (int) round($amount * 100)), $lineTotals);
        $sum = array_sum($weights);
        $remaining = min($sum, max(0, (int) round($discount * 100)));
        $allocated = [];
        foreach ($weights as $weight) {
            $amount = $sum > 0 ? min($weight, (int) round($remaining * $weight / $sum)) : 0;
            $allocated[] = $amount / 100;
            $remaining -= $amount;
            $sum -= $weight;
        }

        return $allocated;
    }
}
