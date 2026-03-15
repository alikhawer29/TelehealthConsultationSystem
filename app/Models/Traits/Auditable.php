<?php 

namespace App\Models\Traits;

use App\Models\AuditLog;

trait Auditable
{
    public static function bootAuditable()
    {
        static::created(function ($model) {
            $model->writeAuditLog('created', null, $model->toArray());
        });

        static::updated(function ($model) {
            $model->writeAuditLog('updated', $model->getOriginal(), $model->getChanges());
        });

        static::deleted(function ($model) {
            $model->writeAuditLog('deleted', $model->toArray(), null);
        });
    }

    protected function writeAuditLog($action, $oldValues = null, $newValues = null)
    {
        AuditLog::create([
            'user_id'    => auth()->id(),
            'action'     => $action,
            'model_type' => get_class($this),
            'model_id'   => $this->id,
            'old_values' => $oldValues,
            'new_values' => $newValues,
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
        ]);
    }
}