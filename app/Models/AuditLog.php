<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Support\Arr;

class AuditLog extends Model
{
    use HasFactory;

    protected $fillable = ['user_id', 'action', 'auditable_type', 'auditable_id', 'description', 'old_values', 'new_values', 'ip_address', 'user_agent'];

    protected function casts(): array
    {
        return ['old_values' => 'array', 'new_values' => 'array'];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function auditable(): MorphTo
    {
        return $this->morphTo();
    }

    /** @param array<string, mixed> $values
     * @return array<string, mixed>
     */
    public static function safeValues(array $values): array
    {
        return Arr::only($values, [
            'name', 'email', 'status', 'slug', 'module', 'description', 'is_system',
            'name_fa', 'description_fa', 'role_ids', 'permission_ids', 'password_changed',
            ...array_keys(Setting::defaults()),
        ]);
    }

    /** @param array<string, mixed> $old
     * @param  array<string, mixed>  $new
     */
    public static function record(string $action, ?Model $subject = null, array $old = [], array $new = []): void
    {
        static::create([
            'user_id' => auth()->id(),
            'action' => $action,
            'auditable_type' => $subject?->getMorphClass(),
            'auditable_id' => $subject?->getKey(),
            'description' => str($action)->replace(['.', '-'], ' ')->headline()->toString(),
            'old_values' => static::safeValues($old),
            'new_values' => static::safeValues($new),
            'ip_address' => request()->ip(),
            'user_agent' => mb_substr(request()->userAgent() ?? '', 0, 1000),
        ]);
    }
}
