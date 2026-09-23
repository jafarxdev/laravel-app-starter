<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Permission extends Model
{
    use HasFactory;

    protected $fillable = ['name', 'slug', 'module', 'description', 'name_fa', 'description_fa'];

    public function localizedName(): string
    {
        return app()->isLocale('fa') ? ($this->name_fa ?: __($this->name)) : $this->name;
    }

    public function localizedDescription(): string
    {
        return app()->isLocale('fa') ? ($this->description_fa ?: __($this->description ?? '')) : ($this->description ?? '');
    }

    public function roles(): BelongsToMany
    {
        return $this->belongsToMany(Role::class);
    }

    public function isCritical(): bool
    {
        return in_array($this->slug, config('starter.permissions', []), true);
    }
}
