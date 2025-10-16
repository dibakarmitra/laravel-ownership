<?php

declare(strict_types=1);

namespace Dibakar\Ownership\Traits;

use Dibakar\Ownership\Models\Ownership as OwnershipModel;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Support\Collection;

trait IsOwner
{
    public static function bootIsOwner(): void
    {
        static::deleting(function (Model $model) {
            if (method_exists($model, 'isForceDeleting') && !$model->isForceDeleting()) {
                return;
            }
            $model->ownerships()->delete();
        });
    }

    public function ownerships(): MorphMany
    {
        return $this->morphMany(
            config('ownership.models.ownership', OwnershipModel::class),
            'owner'
        );
    }

    public function owns(Model $model, ?string $role = null): bool
    {
        $query = $this->ownerships()
            ->where('ownable_type', $model->getMorphClass())
            ->where('ownable_id', $model->getKey());

        return $role ? $query->where('role', $role)->exists() : $query->exists();
    }

    public function getOwnedItems(string $type, ?string $role = null): Collection
    {
        return $this->ownerships()
            ->where('ownable_type', $this->getActualClassNameForMorph($type))
            ->when($role, fn($q) => $q->where('role', $role))
            ->with('ownable')
            ->get()
            ->pluck('ownable');
    }

    public function transferOwnershipTo(Model $newOwner, ?string $type = null): bool
    {
        $query = $this->ownerships();
        
        if ($type) {
            $query->where('ownable_type', $this->getActualClassNameForMorph($type));
        }

        return (bool) $query->update([
            'owner_id' => $newOwner->getKey(),
            'owner_type' => $newOwner->getMorphClass(),
        ]);
    }

    public function countOwnedItems(?string $type = null, ?string $role = null): int
    {
        return $this->ownerships()
            ->when($type, fn($q) => $q->where('ownable_type', $this->getActualClassNameForMorph($type)))
            ->when($role, fn($q) => $q->where('role', $role))
            ->count();
    }
}