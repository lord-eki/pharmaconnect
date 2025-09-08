<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SystemSetting extends Model
{
    use HasFactory;

    protected $fillable = [
        'key',
        'value',
        'type',
        'description',
        'is_public',
        'group',
    ];

    protected $casts = [
        'is_public' => 'boolean',
    ];

    public function getTypedValueAttribute()
    {
        return match ($this->type) {
            'integer' => (int) $this->value,
            'decimal' => (float) $this->value,
            'boolean' => (bool) $this->value,
            'json' => json_decode($this->value, true),
            default => $this->value,
        };
    }

    public static function getValue(string $key, $default = null)
    {
        $setting = static::where('key', $key)->first();
        return $setting ? $setting->typed_value : $default;
    }

    public static function setValue(string $key, $value, string $type = 'string'): void
    {
        $setting = static::firstOrNew(['key' => $key]);
        $setting->value = $type === 'json' ? json_encode($value) : (string) $value;
        $setting->type = $type;
        $setting->save();
    }
}
