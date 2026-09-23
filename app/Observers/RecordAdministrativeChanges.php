<?php

namespace App\Observers;

use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class RecordAdministrativeChanges
{
    public function created(Model $model): void
    {
        if (auth()->check()) {
            AuditLog::record($this->action($model, 'created'), $model, new: $model->getAttributes());
        }
    }

    public function updated(Model $model): void
    {
        if (! auth()->check()) {
            return;
        }

        $new = AuditLog::safeValues($model->getChanges());
        if ($model instanceof User && $model->wasChanged('password')) {
            $new['password_changed'] = true;
        }

        if ($new !== []) {
            AuditLog::record($this->action($model, 'updated'), $model,
                array_intersect_key($model->getRawOriginal(), $new), $new);
        }
    }

    public function deleted(Model $model): void
    {
        if (auth()->check()) {
            AuditLog::record($this->action($model, 'deleted'), $model, $model->getAttributes());
        }
    }

    private function action(Model $model, string $verb): string
    {
        return Str::kebab(class_basename($model)).'.'.$verb;
    }
}
