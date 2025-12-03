<?php

namespace Database\Seeders;

use App\Models\DocumentCategory;
use Illuminate\Database\Seeder;

class DocumentCategorySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $categories = [
            [
                'name' => 'Insurance Claims',
                'description' => 'Documents related to insurance claims and submissions',
                'is_active' => true,
                'sort_order' => 1,
            ],
            [
                'name' => 'Prescriptions',
                'description' => 'Medical prescriptions and related documents',
                'is_active' => true,
                'sort_order' => 2,
            ],
            [
                'name' => 'Invoices',
                'description' => 'Supplier invoices and payment documents',
                'is_active' => true,
                'sort_order' => 3,
            ],
            [
                'name' => 'Delivery Documents',
                'description' => 'Delivery notes, receipts, and proof of delivery',
                'is_active' => true,
                'sort_order' => 4,
            ],
            [
                'name' => 'Purchase Orders',
                'description' => 'LPOs and purchase order documents',
                'is_active' => true,
                'sort_order' => 5,
            ],
            [
                'name' => 'Payment Vouchers',
                'description' => 'Payment vouchers and receipts',
                'is_active' => true,
                'sort_order' => 6,
            ],
            [
                'name' => 'Credit Notes',
                'description' => 'Credit notes and refund documents',
                'is_active' => true,
                'sort_order' => 7,
            ],
            [
                'name' => 'Patient Records',
                'description' => 'Patient-related documents and medical records',
                'is_active' => true,
                'sort_order' => 8,
            ],
            [
                'name' => 'Contracts',
                'description' => 'Supplier and partner contracts',
                'is_active' => true,
                'sort_order' => 9,
            ],
            [
                'name' => 'Compliance Documents',
                'description' => 'Regulatory and compliance documents',
                'is_active' => true,
                'sort_order' => 10,
            ],
            [
                'name' => 'Other',
                'description' => 'Miscellaneous documents',
                'is_active' => true,
                'sort_order' => 999,
            ],
        ];

        foreach ($categories as $category) {
            DocumentCategory::firstOrCreate(
                ['name' => $category['name']],
                $category
            );
        }

        $this->command->info('Document categories seeded successfully!');

    }
}
