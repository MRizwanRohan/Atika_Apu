<?php

namespace App\Services\Offers;

use App\Models\GlobalOffer;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Schema;

class GlobalOfferService
{
    public function activeOffers(): Collection
    {
        // Allows deploying code before the additive migration without breaking carts.
        if (! Schema::hasTable('global_offers')) {
            return collect();
        }

        return GlobalOffer::where('enabled', true)
            ->where(fn ($q) => $q->whereNull('starts_at')->orWhere('starts_at', '<=', now()))
            ->where(fn ($q) => $q->whereNull('ends_at')->orWhere('ends_at', '>', now()))
            ->orderBy('minimum_subtotal')->orderByDesc('priority')->orderBy('id')->get();
    }

    public function nextChangeAt(): ?string
    {
        if (! Schema::hasTable('global_offers')) {
            return null;
        }
        $starts = GlobalOffer::where('enabled', true)->where('starts_at', '>', now())->min('starts_at');
        $ends = GlobalOffer::where('enabled', true)->where('ends_at', '>', now())->min('ends_at');
        $next = collect([$starts, $ends])->filter()->sort()->first();

        return $next ? \Carbon\Carbon::parse($next)->toIso8601String() : null;
    }

    public function evaluate(float $subtotal, float $couponSaving = 0, ?Collection $offers = null): array
    {
        $base = max(0, (int) round($subtotal * 100));
        $coupon = min($base, max(0, (int) round($couponSaving * 100)));
        $offers ??= $this->activeOffers();
        $best = null;
        $bestSaving = 0;
        $free = null;
        $milestones = [];
        foreach ($offers as $offer) {
            $threshold = (int) round((float) $offer->minimum_subtotal * 100);
            if ($threshold <= 0) {
                continue;
            }
            $eligible = $base > 0 && $base >= $threshold;
            $saving = $offer->reward_type === 'percentage'
                ? (int) round($base * (float) $offer->reward_value / 100)
                : ($offer->reward_type === 'fixed' ? (int) round((float) $offer->reward_value * 100) : 0);
            if ($offer->reward_type === 'percentage' && $offer->maximum_discount !== null) {
                $saving = min($saving, (int) round((float) $offer->maximum_discount * 100));
            }
            $saving = min($base, max(0, $saving));
            if ($eligible && $offer->reward_type === 'free_delivery' && ! $free) {
                $free = $offer;
            }
            if ($eligible && $saving > 0 && ($saving > $bestSaving || ($saving === $bestSaving && (! $best || $offer->priority > $best->priority || ($offer->priority === $best->priority && $offer->id < $best->id))))) {
                $best = $offer;
                $bestSaving = $saving;
            }
            $milestones[] = [
                'id' => $offer->id, 'name' => $offer->name, 'reward_type' => $offer->reward_type,
                'reward_value' => $offer->reward_value, 'maximum_discount' => $offer->maximum_discount,
                'threshold' => $threshold / 100, 'remaining' => max(0, $threshold - $base) / 100,
                'percent' => min(100, round($base / $threshold * 100, 2)),
                'eligible' => $eligible, 'saving' => $saving / 100, 'applied' => false,
            ];
        }
        $globalWins = $bestSaving > $coupon;
        $applied = [];
        foreach ($milestones as &$milestone) {
            $milestone['applied'] = ($globalWins && $milestone['id'] === $best?->id) || $milestone['id'] === $free?->id;
            if ($milestone['applied']) {
                $applied[] = $milestone;
            }
        }
        unset($milestone);
        $winningSaving = max($coupon, $bestSaving);
        $next = collect($milestones)->first(function ($m) use ($winningSaving) {
            if ($m['eligible']) {
                return false;
            }
            if ($m['reward_type'] === 'free_delivery') {
                return true;
            }
            $atThreshold = $m['reward_type'] === 'percentage'
                ? round($m['threshold'] * (float) $m['reward_value'] / 100, 2)
                : (float) $m['reward_value'];
            if ($m['maximum_discount'] !== null) {
                $atThreshold = min($atThreshold, (float) $m['maximum_discount']);
            }

            return min($atThreshold, $m['threshold']) * 100 > $winningSaving;
        });

        return [
            'qualifying_subtotal' => $base / 100,
            'coupon_discount' => $globalWins ? 0 : $coupon / 100,
            'coupon_candidate_discount' => $coupon / 100,
            'global_discount' => $globalWins ? $bestSaving / 100 : 0,
            'monetary_discount' => $winningSaving / 100,
            'discount_source' => $globalWins ? 'global_offer' : ($coupon > 0 ? 'coupon' : null),
            'merchandise_payable' => ($base - $winningSaving) / 100,
            'free_delivery' => $free !== null,
            'applied_offers' => $applied, 'milestones' => $milestones, 'next_milestone' => $next,
            'evaluated_at' => now()->toIso8601String(),
            'next_refresh_at' => $this->nextChangeAt(),
        ];
    }
}
