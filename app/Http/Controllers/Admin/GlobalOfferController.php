<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\GlobalOffer;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Inertia\Inertia;

class GlobalOfferController extends Controller
{
    public function index()
    {
        $tiers = GlobalOffer::orderBy('minimum_subtotal')->orderBy('id')->get();

        return Inertia::render('Admin/GlobalOffers/Index', [
            'globalOfferSettings' => [
                'enabled' => $tiers->contains('enabled', true),
                'tiers' => $tiers->map(fn ($tier) => $tier->only(['id', 'minimum_subtotal', 'reward_type', 'reward_value'])),
            ],
        ]);
    }

    public function store(Request $request)
    {
        $data = $this->validated($request);
        DB::transaction(function () use ($data) {
            $existing = GlobalOffer::lockForUpdate()->get()->keyBy('id');
            $retained = [];
            foreach ($data['tiers'] as $index => $tier) {
                $offer = ! empty($tier['id']) ? $existing->get($tier['id']) : new GlobalOffer;
                abort_unless($offer, 422, 'Offer settings changed. Please refresh and try again.');
                $offer->fill([
                    'name' => 'Global shopping reward',
                    'enabled' => $data['enabled'],
                    'minimum_subtotal' => $tier['minimum_subtotal'],
                    'reward_type' => $tier['reward_type'],
                    'reward_value' => $tier['reward_type'] === 'free_delivery' ? null : $tier['reward_value'],
                    'maximum_discount' => null,
                    'priority' => $index,
                    'starts_at' => null,
                    'ends_at' => null,
                ])->save();
                $retained[] = $offer->id;
            }
            GlobalOffer::whereNotIn('id', $retained)->delete();
        });

        return back()->with('success', 'Global offer settings saved.');
    }

    private function validated(Request $request): array
    {
        $rules = [
            'enabled' => ['required', 'boolean'],
            'tiers' => ['required', 'array', 'list', 'min:1', 'max:30'],
            'tiers.*.id' => ['nullable', 'integer', 'distinct', Rule::exists('global_offers', 'id')->whereNull('deleted_at')],
            'tiers.*.minimum_subtotal' => ['required', 'numeric', 'min:0.01', 'max:9999999999'],
            'tiers.*.reward_type' => ['required', 'in:percentage,fixed,free_delivery'],
        ];
        foreach ((array) $request->input('tiers', []) as $index => $tier) {
            $type = is_array($tier) ? ($tier['reward_type'] ?? null) : null;
            $rules["tiers.$index.reward_value"] = $type === 'free_delivery'
                ? ['nullable'] : ['required', 'numeric', 'min:0.01', $type === 'percentage' ? 'max:100' : 'max:9999999999'];
        }

        return $request->validate($rules);
    }
}
