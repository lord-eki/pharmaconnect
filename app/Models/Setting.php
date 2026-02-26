<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class Setting extends Model
{
      protected $fillable = ['key', 'value', 'type', 'group', 'label', 'description'];

    public static function get(string $key, mixed $default = null): mixed
    {
        $setting = Cache::remember("setting_{$key}", 3600, fn () => static::where('key', $key)->first());

        if (! $setting) {
            return $default;
        }

        return static::cast($setting->value, $setting->type);
    }

   
    public static function set(string $key, mixed $value): void
    {
        static::updateOrCreate(
            ['key' => $key],
            ['value' => (string) $value]
        );

        Cache::forget("setting_{$key}");
    }

    public static function deliveryFee(): float
    {
        return (float) static::get('delivery_fee', 100.00);
    }

   
    protected static function cast(mixed $value, string $type): mixed
    {
        return match ($type) {
            'integer' => (int)    $value,
            'float'   => (float)  $value,
            'boolean' => filter_var($value, FILTER_VALIDATE_BOOLEAN),
            'json'    => json_decode($value, true),
            default   => (string) $value,
        };
    }
}
