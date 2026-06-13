<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\DeliveryZone;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

class DeliveryZoneSeeder extends Seeder
{
    public function run(): void
    {
        $jsonPath = public_path('jsondata/checkout_locations.json');
        if (!File::exists($jsonPath)) {
            return;
        }

        $data = json_decode(File::get($jsonPath), true);
        if (!is_array($data)) {
            return;
        }

        $districtAliases = [
            'Barishal' => ['Barisal'],
            'Bogura' => ['Bogra'],
            'Cumilla' => ['Comilla'],
            "Cox's Bazar" => ['Coxsbazar'],
            'Jashore' => ['Jessore'],
            'Jhalokathi' => ['Jhalakathi'],
        ];

        $syncedDistrictIds = [];

        foreach ($data as $district) {
            $districtNames = array_values(array_unique(array_filter([
                $district['district'] ?? null,
                ...($districtAliases[$district['district']] ?? []),
            ])));

            $districtModel = DeliveryZone::where('type', DeliveryZone::TYPE_DISTRICT)
                ->whereIn('name', $districtNames)
                ->first();

            if ($districtModel) {
                $districtModel->update([
                    'name' => $district['district'],
                    'bn_name' => $district['bn'] ?? null,
                    'json_id' => $districtModel->json_id ?: 'district:' . Str::slug($district['district']),
                    'parent_id' => null,
                ]);
            } else {
                $districtModel = DeliveryZone::create([
                    'type' => DeliveryZone::TYPE_DISTRICT,
                    'name' => $district['district'],
                    'bn_name' => $district['bn'] ?? null,
                    'json_id' => 'district:' . Str::slug($district['district']),
                    'parent_id' => null,
                ]);
            }

            $syncedDistrictIds[] = $districtModel->id;

            $thanaJsonIds = [];

            foreach (($district['thanas'] ?? []) as $thana) {
                $thanaJsonIds[] = (string) $thana['id'];

                DeliveryZone::updateOrCreate(
                    [
                        'type' => DeliveryZone::TYPE_THANA,
                        'json_id' => (string) $thana['id'],
                    ],
                    [
                        'name' => $thana['name'],
                        'bn_name' => $thana['bn'] ?? null,
                        'parent_id' => $districtModel->id,
                    ]
                );
            }

            DeliveryZone::where('type', DeliveryZone::TYPE_THANA)
                ->where('parent_id', $districtModel->id)
                ->when(
                    !empty($thanaJsonIds),
                    fn ($query) => $query->whereNotIn('json_id', $thanaJsonIds),
                    fn ($query) => $query
                )
                ->delete();
        }

        $obsoleteDistrictIds = DeliveryZone::where('type', DeliveryZone::TYPE_DISTRICT)
            ->whereNotIn('id', $syncedDistrictIds)
            ->pluck('id');

        if ($obsoleteDistrictIds->isNotEmpty()) {
            DeliveryZone::whereIn('parent_id', $obsoleteDistrictIds)->delete();
            DeliveryZone::whereIn('id', $obsoleteDistrictIds)->delete();
        }
    }
}
