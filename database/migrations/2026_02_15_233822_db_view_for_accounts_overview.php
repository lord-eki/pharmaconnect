<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
          DB::statement("
            CREATE VIEW account_overviews_view AS
            SELECT 
                CONCAT('R-', receivables.id) as id,
                receivables.created_at as date,
                receivables.reference,
                'receivable' as type,
                receivables.payment_source as category,
                CASE 
                    WHEN receivables.payment_source = 'patient' 
                        THEN CONCAT(COALESCE(patients.first_name, ''), ' ', COALESCE(patients.last_name, ''))
                    WHEN receivables.payment_source = 'insurance' 
                        THEN COALESCE(insurance_providers.company_name, 'N/A')
                    ELSE 'N/A'
                END as party_name,
                receivables.amount as amount_in,
                0 as amount_out,
                CASE WHEN receivables.received_at IS NOT NULL THEN 1 ELSE 0 END as is_completed,
                COALESCE(prescriptions.prescription_number, 'N/A') as related_document,
                'receivable' as source_type,
                receivables.id as source_id
            FROM receivables
            LEFT JOIN patients ON receivables.patient_id = patients.id
            LEFT JOIN insurance_providers ON receivables.insurance_provider_id = insurance_providers.id
            LEFT JOIN prescriptions ON receivables.prescription_id = prescriptions.id
            
            UNION ALL
            
            SELECT 
                CONCAT('P-', payables.id) as id,
                payables.created_at as date,
                payables.reference,
                'payable' as type,
                payables.vendor_type as category,
                COALESCE(users.name, users.email, 'N/A') as party_name,
                0 as amount_in,
                payables.amount as amount_out,
                CASE WHEN payables.paid_at IS NOT NULL THEN 1 ELSE 0 END as is_completed,
                COALESCE(orders.order_number, 'N/A') as related_document,
                'payable' as source_type,
                payables.id as source_id
            FROM payables
            LEFT JOIN users ON payables.vendor_id = users.id
            LEFT JOIN orders ON payables.order_id = orders.id
        ");
    }

 
};
