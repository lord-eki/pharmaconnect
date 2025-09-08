<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MedicineInteraction extends Model
{
    use HasFactory;

    protected $fillable = [
        'medicine_id',
        'interacting_medicine_id',
        'interaction_type',
        'description',
        'clinical_significance',
    ];

    public function medicine(): BelongsTo
    {
        return $this->belongsTo(Medicine::class);
    }

    public function interactingMedicine(): BelongsTo
    {
        return $this->belongsTo(Medicine::class, 'interacting_medicine_id');
    }
}
