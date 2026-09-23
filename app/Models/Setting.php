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
            'application_name_fa' => null,
            'short_name_fa' => null,
            'organization_name_fa' => null,
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

    /** @return array<string, string|null> */
    public static function localizedValues(): array
    {
        $settings = app('starter.settings');

        if (app()->isLocale('fa')) {
            foreach (['application_name', 'short_name', 'organization_name'] as $key) {
                $settings[$key] = $settings[$key.'_fa'] ?: $settings[$key];
            }
        }

        return $settings;
    }

    public static function formatDate(CarbonInterface $date): string
    {
        $settings = app('starter.settings');

        return $date->copy()->setTimezone($settings['timezone'])->locale(app()->getLocale())->translatedFormat($settings['date_format']);
    }
}
