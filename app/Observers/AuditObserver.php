<?php

namespace App\Observers;

use App\Models\AuditLog;
use Illuminate\Database\Eloquent\Model;

class AuditObserver
{
    /**
     * Get the event category for the model.
     * Code comments are strictly in English.
     */
    protected function getEventCategory(Model $model): string
    {
        $class = class_basename($model);
        
        return match ($class) {
            'Claim' => 'CLAIM',
            'MileageRate' => 'MILEAGE_CONFIG',
            'Vehicle' => 'VEHICLE',
            'Category' => 'CATEGORY',
            'User' => 'SECURITY',
            default => strtoupper($class),
        };
    }

    /**
     * Get the claim ID if applicable.
     */
    protected function getClaimId(Model $model): ?int
    {
        if (class_basename($model) === 'Claim') {
            return $model->claim_id ?? $model->id; // claim_id might be the custom PK
        }
        
        return null;
    }

    /**
     * Handle the Model "created" event.
     */
    public function created(Model $model): void
    {
        AuditLog::log(
            'CREATED',
            $this->getClaimId($model),
            null,
            $model->getAttributes(),
            auth()->id() ?? 1,
            $this->getEventCategory($model),
            get_class($model),
            $model->getKey()
        );
    }

    /**
     * Handle the Model "updated" event.
     */
    public function updated(Model $model): void
    {
        // Don't log if no changes were made
        if (!$model->isDirty()) {
            return;
        }

        $newValues = $model->getDirty();
        $oldValues = array_intersect_key($model->getOriginal(), $newValues);

        AuditLog::log(
            'UPDATED',
            $this->getClaimId($model),
            $oldValues,
            $newValues,
            auth()->id() ?? 1,
            $this->getEventCategory($model),
            get_class($model),
            $model->getKey()
        );
    }

    /**
     * Handle the Model "deleted" event.
     */
    public function deleted(Model $model): void
    {
        AuditLog::log(
            'DELETED',
            $this->getClaimId($model),
            $model->getAttributes(),
            null,
            auth()->id() ?? 1,
            $this->getEventCategory($model),
            get_class($model),
            $model->getKey()
        );
    }
}
