<?php

namespace App\Models;

use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Setting extends Model
{
    use HasFactory;

    protected $fillable = ['key', 'value'];

    /** @return array<string, string|null> */
    public static function defaults(): array
    {
        return [
            'application_name' => config('app.name'),
            'short_name' => 'ES',
            'organization_name' => 'Your organization',
            'contact_email' => 'contact@example.com',
            'contact_phone' => null,
            'logo_path' => null,
            'timezone' => 'UTC',
            'date_format' => 'Y-m-d',
        ];
    }

    /** @return array<string, string|null> */
    public static function values(): array
    {
        return array_replace(static::defaults(), static::whereIn('key', array_keys(static::defaults()))->pluck('value', 'key')->all());
    }

    public static function formatDate(CarbonInterface $date): string
    {
        $settings = app('starter.settings');

        return $date->copy()->setTimezone($settings['timezone'])->format($settings['date_format']);
    }
}
