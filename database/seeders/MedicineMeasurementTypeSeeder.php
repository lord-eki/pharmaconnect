<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\MedicineCategory;
use App\Models\Medicine;

class MedicineMeasurementTypeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
          // Get or create categories
        $painkillerCategory = MedicineCategory::firstOrCreate(
            ['name' => 'Painkillers & Analgesics'],
            ['description' => 'Pain relief medications', 'is_active' => true]
        );

        $antibioticCategory = MedicineCategory::firstOrCreate(
            ['name' => 'Antibiotics'],
            ['description' => 'Bacterial infection treatments', 'is_active' => true]
        );

        // Sample discrete medicines (tablets/capsules)
        $discreteMedicines = [
            [
                'category_id' => $painkillerCategory->id,
                'generic_name' => 'Paracetamol',
                'brand_name' => 'Panadol',
                'strength' => '500mg',
                'dosage_form' => 'Tablet',
                'measurement_type' => 'discrete',
                'unit_of_measurement' => 'tablets',
                'pack_size' => '20 tablets per strip',
                'manufacturer' => 'GSK',
                'active_ingredients' => 'Paracetamol 500mg',
                'description' => 'Used for pain relief and fever reduction',
                'usage_instructions' => 'Take 1-2 tablets every 4-6 hours as needed',
                'prescription_required' => false,
            ],
            [
                'category_id' => $painkillerCategory->id,
                'generic_name' => 'Ibuprofen',
                'brand_name' => 'Brufen',
                'strength' => '400mg',
                'dosage_form' => 'Tablet',
                'measurement_type' => 'discrete',
                'unit_of_measurement' => 'tablets',
                'pack_size' => '30 tablets per bottle',
                'manufacturer' => 'Abbott',
                'active_ingredients' => 'Ibuprofen 400mg',
                'description' => 'Non-steroidal anti-inflammatory drug',
                'usage_instructions' => 'Take 1 tablet 2-3 times daily with food',
                'prescription_required' => false,
            ],
            [
                'category_id' => $antibioticCategory->id,
                'generic_name' => 'Amoxicillin',
                'brand_name' => 'Amoxil',
                'strength' => '500mg',
                'dosage_form' => 'Capsule',
                'measurement_type' => 'discrete',
                'unit_of_measurement' => 'capsules',
                'pack_size' => '21 capsules per box',
                'manufacturer' => 'GSK',
                'active_ingredients' => 'Amoxicillin 500mg',
                'description' => 'Broad-spectrum antibiotic',
                'usage_instructions' => 'Take 1 capsule 3 times daily',
                'prescription_required' => true,
            ],
        ];

        // Sample volume-based medicines (syrups/injections)
        $volumeMedicines = [
            [
                'category_id' => $painkillerCategory->id,
                'generic_name' => 'Paracetamol',
                'brand_name' => 'Panadol',
                'strength' => '120mg/5ml',
                'dosage_form' => 'Syrup',
                'measurement_type' => 'volume',
                'volume_per_unit' => 100.00, // 100ml bottle
                'unit_of_measurement' => 'ml',
                'pack_size' => '100ml bottle',
                'manufacturer' => 'GSK',
                'active_ingredients' => 'Paracetamol 120mg per 5ml',
                'description' => 'Liquid paracetamol for children and adults who can\'t swallow tablets',
                'usage_instructions' => 'Shake well before use. Dosage varies by age and weight',
                'prescription_required' => false,
            ],
            [
                'category_id' => $antibioticCategory->id,
                'generic_name' => 'Amoxicillin',
                'brand_name' => 'Amoxil',
                'strength' => '125mg/5ml',
                'dosage_form' => 'Suspension',
                'measurement_type' => 'volume',
                'volume_per_unit' => 100.00, // 100ml bottle
                'unit_of_measurement' => 'ml',
                'pack_size' => '100ml bottle',
                'manufacturer' => 'GSK',
                'active_ingredients' => 'Amoxicillin 125mg per 5ml',
                'description' => 'Liquid antibiotic for pediatric use',
                'usage_instructions' => 'Reconstitute with water. Shake well before each use',
                'prescription_required' => true,
            ],
            [
                'category_id' => $painkillerCategory->id,
                'generic_name' => 'Ibuprofen',
                'brand_name' => 'Brufen',
                'strength' => '100mg/5ml',
                'dosage_form' => 'Syrup',
                'measurement_type' => 'volume',
                'volume_per_unit' => 200.00, // 200ml bottle
                'unit_of_measurement' => 'ml',
                'pack_size' => '200ml bottle',
                'manufacturer' => 'Abbott',
                'active_ingredients' => 'Ibuprofen 100mg per 5ml',
                'description' => 'Liquid NSAID for fever and pain',
                'usage_instructions' => 'Dosage based on weight: 5-10mg/kg every 6-8 hours',
                'prescription_required' => false,
            ],
            [
                'category_id' => $antibioticCategory->id,
                'generic_name' => 'Ceftriaxone',
                'brand_name' => 'Rocephin',
                'strength' => '1g',
                'dosage_form' => 'Injection',
                'measurement_type' => 'volume',
                'volume_per_unit' => 10.00, // 10ml vial after reconstitution
                'unit_of_measurement' => 'ml',
                'pack_size' => '1 vial',
                'manufacturer' => 'Roche',
                'active_ingredients' => 'Ceftriaxone 1g',
                'description' => 'Injectable antibiotic for serious infections',
                'usage_instructions' => 'For IM or IV use. Reconstitute before use',
                'prescription_required' => true,
            ],
        ];

        // Insert discrete medicines
        foreach ($discreteMedicines as $medicine) {
            Medicine::updateOrCreate(
                [
                    'generic_name' => $medicine['generic_name'],
                    'dosage_form' => $medicine['dosage_form'],
                    'strength' => $medicine['strength'],
                ],
                array_merge($medicine, [
                    'is_active' => true,
                    'controlled_substance' => false,
                ])
            );
        }

        // Insert volume-based medicines
        foreach ($volumeMedicines as $medicine) {
            Medicine::updateOrCreate(
                [
                    'generic_name' => $medicine['generic_name'],
                    'dosage_form' => $medicine['dosage_form'],
                    'strength' => $medicine['strength'],
                ],
                array_merge($medicine, [
                    'is_active' => true,
                    'controlled_substance' => false,
                ])
            );
        }

        $this->command->info('Medicine measurement types seeded successfully!');
        $this->command->info('Discrete medicines: ' . count($discreteMedicines));
        $this->command->info('Volume-based medicines: ' . count($volumeMedicines));
    }
}
