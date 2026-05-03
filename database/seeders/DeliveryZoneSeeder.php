<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

use App\Models\DeliveryZone;
use Illuminate\Support\Facades\File;

class DeliveryZoneSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $jsonPath = public_path('jsondata/bangladesh_hierarchy.json');
        if (!File::exists($jsonPath)) {
            return;
        }

        $data = json_decode(File::get($jsonPath), true);

        foreach ($data as $division) {
            foreach ($division['districts'] as $district) {
                // Create or Update District
                $districtModel = DeliveryZone::updateOrCreate(
                    ['type' => 'district', 'json_id' => $district['id']],
                    [
                        'name' => $district['name'],
                        'bn_name' => $district['bn_name'],
                        'parent_id' => null,
                        // Set default charges if needed, or leave null for admin to set
                    ]
                );

                foreach ($district['upazilas'] as $upazila) {
                    // Create or Update Upazila (Thana)
                    DeliveryZone::updateOrCreate(
                        ['type' => 'upazila', 'json_id' => $upazila['id']],
                        [
                            'name' => $upazila['name'],
                            'bn_name' => $upazila['bn_name'],
                            'parent_id' => $districtModel->id,
                        ]
                    );
                }
            }
        }
    }
}
