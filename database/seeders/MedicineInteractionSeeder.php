<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class MedicineInteractionSeeder extends Seeder
{
 public function run()
    {
        $interactions = [
            [
                'medicine_id' => 1, // Amoxicillin
                'interacting_medicine_id' => 13, // Metformin
                'interaction_type' => 'minor',
                'description' => 'Amoxicillin may reduce metformin absorption',
                'clinical_significance' => 'Monitor blood glucose levels more frequently'
            ],
            [
                'medicine_id' => 4, // Ibuprofen
                'interacting_medicine_id' => 7, // Atenolol
                'interaction_type' => 'moderate',
                'description' => 'NSAIDs may reduce the antihypertensive effect of beta-blockers',
                'clinical_significance' => 'Monitor blood pressure, consider alternative pain relief'
            ],
            [
                'medicine_id' => 6, // Diclofenac
                'interacting_medicine_id' => 13, // Metformin
                'interaction_type' => 'moderate',
                'description' => 'NSAIDs may impair kidney function and affect metformin clearance',
                'clinical_significance' => 'Monitor kidney function and blood glucose'
            ],
            [
                'medicine_id' => 11, // Omeprazole
                'interacting_medicine_id' => 16, // Carbamazepine
                'interaction_type' => 'major',
                'description' => 'Omeprazole inhibits carbamazepine metabolism',
                'clinical_significance' => 'May increase carbamazepine levels and toxicity risk'
            ],
        ];

        foreach ($interactions as $interaction) {
            \App\Models\MedicineInteraction::create($interaction);
        }
    }
}
