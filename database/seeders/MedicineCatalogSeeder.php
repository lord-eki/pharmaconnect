<?php

namespace Database\Seeders;

use App\Models\Medicine;
use App\Models\MedicineCategory;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class MedicineCatalogSeeder extends Seeder
{
    public function run()
    {
        // First seed medicine categories
        $this->seedMedicineCategories();
        
        // Then seed medicines
        $this->seedMedicines();
    }

    private function seedMedicineCategories()
    {
        $categories = [
            // Main Categories
            ['name' => 'Antibiotics', 'description' => 'Antimicrobial medications used to treat bacterial infections', 'parent_id' => null, 'sort_order' => 1],
            ['name' => 'Analgesics', 'description' => 'Pain relief medications', 'parent_id' => null, 'sort_order' => 2],
            ['name' => 'Cardiovascular', 'description' => 'Heart and blood vessel medications', 'parent_id' => null, 'sort_order' => 3],
            ['name' => 'Respiratory', 'description' => 'Medications for respiratory conditions', 'parent_id' => null, 'sort_order' => 4],
            ['name' => 'Gastrointestinal', 'description' => 'Digestive system medications', 'parent_id' => null, 'sort_order' => 5],
            ['name' => 'Dermatological', 'description' => 'Skin condition medications', 'parent_id' => null, 'sort_order' => 6],
            ['name' => 'Endocrine', 'description' => 'Hormonal and metabolic medications', 'parent_id' => null, 'sort_order' => 7],
            ['name' => 'Neurological', 'description' => 'Nervous system medications', 'parent_id' => null, 'sort_order' => 8],
            ['name' => 'Antimalarials', 'description' => 'Malaria treatment and prevention', 'parent_id' => null, 'sort_order' => 9],
            ['name' => 'Vitamins & Supplements', 'description' => 'Nutritional supplements and vitamins', 'parent_id' => null, 'sort_order' => 10],
            ['name' => 'Pediatric', 'description' => 'Medications specifically for children', 'parent_id' => null, 'sort_order' => 11],
            ['name' => 'Maternal Health', 'description' => 'Pregnancy and reproductive health medications', 'parent_id' => null, 'sort_order' => 12],
        ];

        foreach ($categories as $category) {
            MedicineCategory::create($category);
        }

        // Add subcategories
        $subcategories = [
            // Antibiotic subcategories
            ['name' => 'Penicillins', 'description' => 'Beta-lactam antibiotics', 'parent_id' => 1, 'sort_order' => 1],
            ['name' => 'Cephalosporins', 'description' => 'Broad-spectrum antibiotics', 'parent_id' => 1, 'sort_order' => 2],
            ['name' => 'Macrolides', 'description' => 'Protein synthesis inhibitors', 'parent_id' => 1, 'sort_order' => 3],
            ['name' => 'Quinolones', 'description' => 'DNA synthesis inhibitors', 'parent_id' => 1, 'sort_order' => 4],
            
            // Analgesic subcategories
            ['name' => 'NSAIDs', 'description' => 'Non-steroidal anti-inflammatory drugs', 'parent_id' => 2, 'sort_order' => 1],
            ['name' => 'Opioid Analgesics', 'description' => 'Narcotic pain relievers', 'parent_id' => 2, 'sort_order' => 2],
            ['name' => 'Antipyretics', 'description' => 'Fever-reducing medications', 'parent_id' => 2, 'sort_order' => 3],
        ];

        foreach ($subcategories as $subcategory) {
            MedicineCategory::create($subcategory);
        }
    }

    private function seedMedicines()
    {
        $medicines = [
            // ANTIBIOTICS
            [
                'category_id' => 13, // Penicillins
                'generic_name' => 'Amoxicillin',
                'brand_name' => 'Amoxil',
                'strength' => '500mg',
                'dosage_form' => 'Capsule',
                'pack_size' => '21 capsules',
                'manufacturer' => 'GlaxoSmithKline',
                'active_ingredients' => 'Amoxicillin trihydrate',
                'description' => 'Broad-spectrum penicillin antibiotic',
                'usage_instructions' => 'Take 500mg three times daily for 7-10 days',
                'side_effects' => 'Nausea, diarrhea, skin rash, allergic reactions',
                'contraindications' => 'Penicillin allergy, infectious mononucleosis',
                'storage_requirements' => 'Store at room temperature, keep dry',
                'prescription_required' => true,
                'controlled_substance' => false,
                'ppb_registration_number' => 'PPB/13245/2023',
            ],
            [
                'category_id' => 13, // Penicillins
                'generic_name' => 'Amoxicillin + Clavulanic Acid',
                'brand_name' => 'Augmentin',
                'strength' => '625mg',
                'dosage_form' => 'Tablet',
                'pack_size' => '14 tablets',
                'manufacturer' => 'GlaxoSmithKline',
                'active_ingredients' => 'Amoxicillin 500mg, Clavulanic acid 125mg',
                'description' => 'Penicillin with beta-lactamase inhibitor',
                'usage_instructions' => 'Take 625mg twice daily with meals',
                'side_effects' => 'Diarrhea, nausea, skin reactions',
                'contraindications' => 'Penicillin allergy, severe hepatic impairment',
                'storage_requirements' => 'Store below 25°C',
                'prescription_required' => true,
                'controlled_substance' => false,
                'ppb_registration_number' => 'PPB/13246/2023',
            ],
            [
                'category_id' => 14, // Cephalosporins
                'generic_name' => 'Ceftriaxone',
                'brand_name' => 'Rocephin',
                'strength' => '1g',
                'dosage_form' => 'Injection',
                'pack_size' => '1 vial',
                'manufacturer' => 'Roche',
                'active_ingredients' => 'Ceftriaxone sodium',
                'description' => 'Third-generation cephalosporin antibiotic',
                'usage_instructions' => 'Administered by healthcare professional',
                'side_effects' => 'Injection site reactions, diarrhea, allergic reactions',
                'contraindications' => 'Hypersensitivity to cephalosporins',
                'storage_requirements' => 'Store at 2-8°C, protect from light',
                'prescription_required' => true,
                'controlled_substance' => false,
                'ppb_registration_number' => 'PPB/13247/2023',
            ],

            // ANALGESICS
            [
                'category_id' => 16, // NSAIDs
                'generic_name' => 'Ibuprofen',
                'brand_name' => 'Brufen',
                'strength' => '400mg',
                'dosage_form' => 'Tablet',
                'pack_size' => '30 tablets',
                'manufacturer' => 'Abbott',
                'active_ingredients' => 'Ibuprofen',
                'description' => 'Non-steroidal anti-inflammatory drug',
                'usage_instructions' => 'Take 400mg 3-4 times daily with food',
                'side_effects' => 'Stomach upset, headache, dizziness',
                'contraindications' => 'Peptic ulcer, severe heart failure, pregnancy (3rd trimester)',
                'storage_requirements' => 'Store at room temperature',
                'prescription_required' => false,
                'controlled_substance' => false,
                'ppb_registration_number' => 'PPB/13248/2023',
            ],
            [
                'category_id' => 18, // Antipyretics
                'generic_name' => 'Paracetamol',
                'brand_name' => 'Panadol',
                'strength' => '500mg',
                'dosage_form' => 'Tablet',
                'pack_size' => '24 tablets',
                'manufacturer' => 'GlaxoSmithKline',
                'active_ingredients' => 'Paracetamol',
                'description' => 'Analgesic and antipyretic',
                'usage_instructions' => 'Take 500mg-1g every 4-6 hours, max 4g daily',
                'side_effects' => 'Rare at recommended doses, liver toxicity with overdose',
                'contraindications' => 'Severe liver disease, alcohol dependence',
                'storage_requirements' => 'Store below 30°C',
                'prescription_required' => false,
                'controlled_substance' => false,
                'ppb_registration_number' => 'PPB/13249/2023',
            ],
            [
                'category_id' => 16, // NSAIDs
                'generic_name' => 'Diclofenac',
                'brand_name' => 'Voltaren',
                'strength' => '50mg',
                'dosage_form' => 'Tablet',
                'pack_size' => '20 tablets',
                'manufacturer' => 'Novartis',
                'active_ingredients' => 'Diclofenac sodium',
                'description' => 'NSAID for pain and inflammation',
                'usage_instructions' => 'Take 50mg 2-3 times daily with food',
                'side_effects' => 'GI upset, headache, dizziness',
                'contraindications' => 'Active peptic ulcer, severe heart failure',
                'storage_requirements' => 'Store at room temperature',
                'prescription_required' => true,
                'controlled_substance' => false,
                'ppb_registration_number' => 'PPB/13250/2023',
            ],

            // CARDIOVASCULAR
            [
                'category_id' => 3, // Cardiovascular
                'generic_name' => 'Amlodipine',
                'brand_name' => 'Norvasc',
                'strength' => '5mg',
                'dosage_form' => 'Tablet',
                'pack_size' => '30 tablets',
                'manufacturer' => 'Pfizer',
                'active_ingredients' => 'Amlodipine besylate',
                'description' => 'Calcium channel blocker for hypertension',
                'usage_instructions' => 'Take 5mg once daily, may increase to 10mg',
                'side_effects' => 'Ankle swelling, dizziness, flushing',
                'contraindications' => 'Severe aortic stenosis, unstable angina',
                'storage_requirements' => 'Store below 30°C',
                'prescription_required' => true,
                'controlled_substance' => false,
                'ppb_registration_number' => 'PPB/13251/2023',
            ],
            [
                'category_id' => 3, // Cardiovascular
                'generic_name' => 'Atenolol',
                'brand_name' => 'Tenormin',
                'strength' => '50mg',
                'dosage_form' => 'Tablet',
                'pack_size' => '28 tablets',
                'manufacturer' => 'AstraZeneca',
                'active_ingredients' => 'Atenolol',
                'description' => 'Beta-blocker for hypertension and angina',
                'usage_instructions' => 'Take 50mg once daily, may increase to 100mg',
                'side_effects' => 'Fatigue, cold extremities, bradycardia',
                'contraindications' => 'Asthma, severe bradycardia, heart block',
                'storage_requirements' => 'Store below 25°C',
                'prescription_required' => true,
                'controlled_substance' => false,
                'ppb_registration_number' => 'PPB/13252/2023',
            ],

            // ANTIMALARIALS
            [
                'category_id' => 9, // Antimalarials
                'generic_name' => 'Artemether + Lumefantrine',
                'brand_name' => 'Coartem',
                'strength' => '20mg/120mg',
                'dosage_form' => 'Tablet',
                'pack_size' => '24 tablets',
                'manufacturer' => 'Novartis',
                'active_ingredients' => 'Artemether 20mg, Lumefantrine 120mg',
                'description' => 'Artemisinin-based combination therapy for malaria',
                'usage_instructions' => 'Take 4 tablets at 0, 8, 24, 36, 48, 60 hours with food',
                'side_effects' => 'Headache, dizziness, nausea, fatigue',
                'contraindications' => 'Severe malaria, known hypersensitivity',
                'storage_requirements' => 'Store below 30°C',
                'prescription_required' => true,
                'controlled_substance' => false,
                'ppb_registration_number' => 'PPB/13253/2023',
            ],
            [
                'category_id' => 9, // Antimalarials
                'generic_name' => 'Quinine Sulphate',
                'brand_name' => 'Qualaquin',
                'strength' => '300mg',
                'dosage_form' => 'Tablet',
                'pack_size' => '28 tablets',
                'manufacturer' => 'Cosmos Pharmaceuticals',
                'active_ingredients' => 'Quinine sulphate',
                'description' => 'Antimalarial for severe malaria treatment',
                'usage_instructions' => 'Take 600mg every 8 hours for 7 days',
                'side_effects' => 'Cinchonism, tinnitus, hearing loss, nausea',
                'contraindications' => 'G6PD deficiency, myasthenia gravis',
                'storage_requirements' => 'Store in dry place below 30°C',
                'prescription_required' => true,
                'controlled_substance' => false,
                'ppb_registration_number' => 'PPB/13254/2023',
            ],

            // RESPIRATORY
            [
                'category_id' => 4, // Respiratory
                'generic_name' => 'Salbutamol',
                'brand_name' => 'Ventolin',
                'strength' => '100mcg/dose',
                'dosage_form' => 'Inhaler',
                'pack_size' => '200 doses',
                'manufacturer' => 'GlaxoSmithKline',
                'active_ingredients' => 'Salbutamol sulphate',
                'description' => 'Beta-2 agonist bronchodilator',
                'usage_instructions' => '1-2 puffs every 4-6 hours as needed',
                'side_effects' => 'Tremor, palpitations, headache',
                'contraindications' => 'Hypersensitivity to salbutamol',
                'storage_requirements' => 'Store below 30°C, do not freeze',
                'prescription_required' => false,
                'controlled_substance' => false,
                'ppb_registration_number' => 'PPB/13255/2023',
            ],

            // GASTROINTESTINAL
            [
                'category_id' => 5, // Gastrointestinal
                'generic_name' => 'Omeprazole',
                'brand_name' => 'Losec',
                'strength' => '20mg',
                'dosage_form' => 'Capsule',
                'pack_size' => '28 capsules',
                'manufacturer' => 'AstraZeneca',
                'active_ingredients' => 'Omeprazole magnesium',
                'description' => 'Proton pump inhibitor for acid reduction',
                'usage_instructions' => 'Take 20mg once daily before breakfast',
                'side_effects' => 'Headache, nausea, diarrhea, abdominal pain',
                'contraindications' => 'Hypersensitivity to PPIs',
                'storage_requirements' => 'Store below 25°C in dry place',
                'prescription_required' => false,
                'controlled_substance' => false,
                'ppb_registration_number' => 'PPB/13256/2023',
            ],

            // ENDOCRINE (Diabetes)
            [
                'category_id' => 7, // Endocrine
                'generic_name' => 'Metformin',
                'brand_name' => 'Glucophage',
                'strength' => '500mg',
                'dosage_form' => 'Tablet',
                'pack_size' => '60 tablets',
                'manufacturer' => 'Merck',
                'active_ingredients' => 'Metformin hydrochloride',
                'description' => 'Biguanide antidiabetic medication',
                'usage_instructions' => 'Take 500mg twice daily with meals',
                'side_effects' => 'Nausea, diarrhea, metallic taste, vitamin B12 deficiency',
                'contraindications' => 'Severe kidney disease, metabolic acidosis',
                'storage_requirements' => 'Store at room temperature',
                'prescription_required' => true,
                'controlled_substance' => false,
                'ppb_registration_number' => 'PPB/13257/2023',
            ],

            // VITAMINS & SUPPLEMENTS
            [
                'category_id' => 10, // Vitamins & Supplements
                'generic_name' => 'Folic Acid',
                'brand_name' => 'Folicard',
                'strength' => '5mg',
                'dosage_form' => 'Tablet',
                'pack_size' => '28 tablets',
                'manufacturer' => 'Roche',
                'active_ingredients' => 'Folic acid',
                'description' => 'Vitamin B9 supplement',
                'usage_instructions' => 'Take 5mg once daily',
                'side_effects' => 'Rare: nausea, bloating',
                'contraindications' => 'Megaloblastic anemia due to B12 deficiency',
                'storage_requirements' => 'Store in dry place below 25°C',
                'prescription_required' => false,
                'controlled_substance' => false,
                'ppb_registration_number' => 'PPB/13258/2023',
            ],

            // PEDIATRIC
            [
                'category_id' => 11, // Pediatric
                'generic_name' => 'Paracetamol',
                'brand_name' => 'Calpol',
                'strength' => '120mg/5ml',
                'dosage_form' => 'Syrup',
                'pack_size' => '100ml bottle',
                'manufacturer' => 'Johnson & Johnson',
                'active_ingredients' => 'Paracetamol',
                'description' => 'Pediatric analgesic and antipyretic syrup',
                'usage_instructions' => '2.5-5ml every 4-6 hours for children 3months-6years',
                'side_effects' => 'Rare at recommended doses',
                'contraindications' => 'Severe liver disease',
                'storage_requirements' => 'Store below 30°C',
                'prescription_required' => false,
                'controlled_substance' => false,
                'ppb_registration_number' => 'PPB/13259/2023',
            ],

            // MATERNAL HEALTH
            [
                'category_id' => 12, // Maternal Health
                'generic_name' => 'Iron + Folic Acid',
                'brand_name' => 'Pregnacare',
                'strength' => '65mg + 400mcg',
                'dosage_form' => 'Tablet',
                'pack_size' => '30 tablets',
                'manufacturer' => 'Vitabiotics',
                'active_ingredients' => 'Ferrous fumarate 65mg, Folic acid 400mcg',
                'description' => 'Iron and folic acid supplement for pregnancy',
                'usage_instructions' => 'Take 1 tablet daily with water',
                'side_effects' => 'Constipation, nausea, dark stools',
                'contraindications' => 'Hemochromatosis, hemosiderosis',
                'storage_requirements' => 'Store in dry place below 25°C',
                'prescription_required' => false,
                'controlled_substance' => false,
                'ppb_registration_number' => 'PPB/13260/2023',
            ],

            // DERMATOLOGICAL
            [
                'category_id' => 6, // Dermatological
                'generic_name' => 'Hydrocortisone',
                'brand_name' => 'Dermacort',
                'strength' => '1%',
                'dosage_form' => 'Cream',
                'pack_size' => '30g tube',
                'manufacturer' => 'Taro Pharmaceuticals',
                'active_ingredients' => 'Hydrocortisone acetate',
                'description' => 'Topical corticosteroid for skin inflammation',
                'usage_instructions' => 'Apply thin layer 2-3 times daily',
                'side_effects' => 'Skin thinning with prolonged use',
                'contraindications' => 'Viral skin infections, fungal infections',
                'storage_requirements' => 'Store below 25°C',
                'prescription_required' => false,
                'controlled_substance' => false,
                'ppb_registration_number' => 'PPB/13261/2023',
            ],

            // NEUROLOGICAL
            [
                'category_id' => 8, // Neurological
                'generic_name' => 'Carbamazepine',
                'brand_name' => 'Tegretol',
                'strength' => '200mg',
                'dosage_form' => 'Tablet',
                'pack_size' => '50 tablets',
                'manufacturer' => 'Novartis',
                'active_ingredients' => 'Carbamazepine',
                'description' => 'Anticonvulsant for epilepsy and neuropathic pain',
                'usage_instructions' => 'Start 100mg twice daily, increase gradually',
                'side_effects' => 'Dizziness, drowsiness, nausea, skin reactions',
                'contraindications' => 'AV heart block, acute porphyria',
                'storage_requirements' => 'Store below 30°C in dry place',
                'prescription_required' => true,
                'controlled_substance' => false,
                'ppb_registration_number' => 'PPB/13262/2023',
            ],
        ];

        foreach ($medicines as $medicine) {
            Medicine::create($medicine);
        }
    }
}
