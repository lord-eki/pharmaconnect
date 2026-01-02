<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class InsuranceFormTemplate extends Model
{
    

    protected $fillable = [
        'insurance_provider_id','template_name','template_path',
        'template_type','template_config','version','is_active'
    ];

    protected $casts = [
        'template_config' => 'array',
        'is_active' => 'boolean',
    ];


    public function insuranceProvider()
    {
        return $this->belongsTo(InsuranceProvider::class);
    }

    public function getFieldMappings(): array
    {
        return $this->template_config['field_mappings'] ?? [];
    }
}
