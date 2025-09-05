<?php

declare(strict_types=1);

namespace Dibakar\Ownership\Traits;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Support\Collection;
use Dibakar\Ownership\Models\Ownership as OwnershipModel;
use Dibakar\Ownership\Events\OwnershipCreated;
use Dibakar\Ownership\Events\OwnershipDeleted;
use Dibakar\Ownership\Events\OwnershipTransferred;
use Dibakar\Ownership\Events\OwnershipUpdated;
use Dibakar\Ownership\Exceptions\InvalidOwnerException;
use Dibakar\Ownership\Facades\Ownership;
use Dibakar\Ownership\Scopes\OwnedByCurrentScope;

trait HasOwnership
{
    public static function bootHasOwnership(): void
    {
        static::creating(function (Model $model) {
            if (config('ownership.mode') === 'single') {
                $name = config('ownership.morph_name', 'owner');
                $idAttr = "{$name}_id";
                $typeAttr = "{$name}_type";

                if (!$model->getAttribute($idAttr) && !$model->getAttribute($typeAttr)) {
                    if ($owner = Ownership::current()) {
                        $model->{$typeAttr} = $owner->getMorphClass();
                        $model->{$idAttr}   = $owner->getKey();
                    }
                }
            }
        });

        static::deleting(function (Model $model) {
            if (config('ownership.mode') === 'multiple') {
                if (method_exists($model, 'isForceDeleting') && ! $model->isForceDeleting()) {
                    return;
                }
                $model->ownerships()->delete();
            }
        });

        if (config('ownership.apply_global_scope', true) && config('ownership.mode') === 'single') {
            static::addGlobalScope(new OwnedByCurrentScope);
        }
    }

    public function owner(): MorphTo
    {
        return $this->morphTo(config('ownership.morph_name', 'owner'));
    }

    public function scopeOwnedBy(Builder $query, $owner): Builder
    {
        if (config('ownership.mode') === 'single') {
            $name = config('ownership.morph_name', 'owner');
            $ownerModel = $owner instanceof Model ? $owner : Ownership::current();
            if (!$ownerModel) return $query;

            return $query
                ->where("{$this->getTable()}.{$name}_type", $ownerModel->getMorphClass())
                ->where("{$this->getTable()}.{$name}_id", $ownerModel->getKey());
        }

        return $query->whereHas('ownerships', function ($q) use ($owner) {
            $ownerModel = $owner instanceof Model ? $owner : Ownership::current();
            if ($ownerModel) {
                $q->where('owner_id', $ownerModel->getKey())
                    ->where('owner_type', $ownerModel->getMorphClass());
            }
        });
    }

    public function isOwnedBy($owner): bool
    {
        if (config('ownership.mode') === 'single') {
            $name = config('ownership.morph_name', 'owner');
            $ownerModel = $owner instanceof Model ? $owner : Ownership::current();
            if (!$ownerModel) return false;

            return $this->getAttribute("{$name}_type") === $ownerModel->getMorphClass()
                && (string) $this->getAttribute("{$name}_id") === (string) $ownerModel->getKey();
        }

        return $this->hasOwner($owner);
    }

    public function ownerships(): MorphMany
    {
        return $this->morphMany(OwnershipModel::class, 'ownable');
    }

    public function addOwner(
        Model $owner,
        ?string $role = null,
        ?array $permissions = null,
        bool $fireEvent = true
    ): OwnershipModel {
        $role = $role ?? config('ownership.multiple_ownership.default_role');

        if (! $this->isValidRole($role)) {
            throw new InvalidOwnerException("The role [{$role}] is not valid.");
        }

        $existingOwnership = $this->ownerships()
            ->where('owner_id', $owner->getKey())
            ->where('owner_type', $owner->getMorphClass())
            ->first();

        if ($existingOwnership) {
            $existingOwnership->update([
                'role' => $role,
                'permissions' => $permissions ? json_encode($permissions) : null,
            ]);
            return $existingOwnership;
        }

        $ownership = $this->ownerships()->create([
            'owner_id' => $owner->getKey(),
            'owner_type' => $owner->getMorphClass(),
            'role' => $role,
            'permissions' => $permissions ? json_encode($permissions) : null,
        ]);

        if ($fireEvent && config('ownership.events.ownership_created', true)) {
            event(new OwnershipCreated($this, $ownership));
        }

        return $ownership;
    }

    public function addOwners(
        array $owners,
        ?string $role = null,
        ?array $permissions = null,
        bool $fireEvent = true
    ): array {
        $added = [];
    
        foreach ($owners as $owner) {
            if (! $owner instanceof Model) {
                throw new InvalidOwnerException("Each owner must be an instance of Illuminate\\Database\\Eloquent\\Model.");
            }
    
            $added[] = $this->addOwner($owner, $role, $permissions, $fireEvent);
        }
    
        return $added;
    }    

    public function setOwner(Model $owner, bool $fireEvent = true): bool
    {
        if (config('ownership.mode') !== 'single') {
            throw new InvalidOwnerException("setOwner is only available in single ownership mode.");
        }

        $name = config('ownership.morph_name', 'owner');

        $this->setAttribute("{$name}_id", $owner->getKey());
        $this->setAttribute("{$name}_type", $owner->getMorphClass());
        $saved = $this->save();

        if ($saved && $fireEvent && config('ownership.events.ownership_updated', true)) {
            event(new OwnershipUpdated($this, [
                'owner_id' => $owner->getKey(),
                'owner_type' => $owner->getMorphClass(),
            ]));
        }

        return $saved;
    }

    public function removeOwner(Model $owner, bool $fireEvent = true): bool
    {
        $deleted = $this->ownerships()
            ->where('owner_id', $owner->getKey())
            ->where('owner_type', $owner->getMorphClass())
            ->delete();

        if ($deleted > 0 && $fireEvent && config('ownership.events.ownership_deleted', true)) {
            event(new OwnershipDeleted($this, [
                'owner_id' => $owner->getKey(),
                'owner_type' => $owner->getMorphClass(),
            ]));
        }

        return $deleted > 0;
    }

    public function clearOwner(bool $fireEvent = true): bool
    {
        if (config('ownership.mode') !== 'single') {
            throw new InvalidOwnerException("clearOwner is only available in single ownership mode.");
        }

        $name = config('ownership.morph_name', 'owner');
        $this->setAttribute("{$name}_id", null);
        $this->setAttribute("{$name}_type", null);
        $saved = $this->save();

        if ($saved && $fireEvent && config('ownership.events.ownership_deleted', true)) {
            event(new OwnershipDeleted($this, [
                'owner_id' => null,
                'owner_type' => null,
            ]));
        }

        return $saved;
    }

    public function hasOwner(Model $owner): bool
    {
        return $this->ownerships()
            ->where('owner_id', $owner->getKey())
            ->where('owner_type', $owner->getMorphClass())
            ->exists();
    }

    public function getOwner(): ?Model
    {
        if (config('ownership.mode') !== 'single') {
            throw new InvalidOwnerException("getOwner is only available in single ownership mode.");
        }

        return $this->owner;
    }

    public function getOwners(?string $ownerType = null): Collection
    {
        $query = $this->ownerships();
        if ($ownerType) {
            $query->where('owner_type', $ownerType);
        }
        return $query->get()->map(fn ($ownership) => $ownership->owner);
    }

    public function transferOwnership(
        Model $from,
        Model $to,
        bool $fireEvent = true
    ): bool {
        if (config('ownership.mode') === 'single') {
            return $this->setOwner($to, $fireEvent);
        }

        $ownership = $this->getOwnershipRecord($from);
        if (! $ownership) return false;

        $ownership->update([
            'owner_id' => $to->getKey(),
            'owner_type' => $to->getMorphClass(),
        ]);

        if ($fireEvent && config('ownership.events.ownership_transferred', true)) {
            event(new OwnershipTransferred($this, $from, $to));
        }

        return true;
    }

    public function getOwnersWithRole(string $role): Collection
    {
        return $this->ownerships()
            ->where('role', $role)
            ->get()
            ->map(fn ($ownership) => $ownership->owner);
    }

    public function hasOwnerWithRole(Model $owner, string $role): bool
    {
        return $this->ownerships()
            ->where('owner_id', $owner->getKey())
            ->where('owner_type', $owner->getMorphClass())
            ->where('role', $role)
            ->exists();
    }

    public function updateOwnerRole(Model $owner, string $role, bool $fireEvent = true): bool
    {
        if (! $this->isValidRole($role)) {
            throw new InvalidOwnerException("The role [{$role}] is not valid.");
        }

        $updated = $this->ownerships()
            ->where('owner_id', $owner->getKey())
            ->where('owner_type', $owner->getMorphClass())
            ->update(['role' => $role]);

        if ($updated > 0 && $fireEvent && config('ownership.events.ownership_updated', true)) {
            event(new OwnershipUpdated($this, [
                'owner_id' => $owner->getKey(),
                'owner_type' => $owner->getMorphClass(),
                'role' => $role,
            ]));
        }

        return $updated > 0;
    }

    public function syncOwners(array $owners, ?string $role = null, ?array $permissions = null): void
    {
        if (config('ownership.mode') !== 'multiple') {
            throw new InvalidOwnerException("syncOwners is only available in multiple ownership mode.");
        }

        $this->ownerships()->delete();

        foreach ($owners as $owner) {
            $this->addOwner($owner, $role, $permissions, false);
        }
    }

    public function clearAllOwners(): void
    {
        if (config('ownership.mode') === 'multiple') {
            $this->ownerships()->delete();
        } else {
            $this->clearOwner();
        }
    }

    public function ownersCount(): int
    {
        if (config('ownership.mode') === 'single') {
            return $this->getOwner() ? 1 : 0;
        }
        return $this->ownerships()->count();
    }

    public function getOwnerRole(): ?string
    {
        if (config('ownership.mode') === 'single') {
            return $this->getAttribute(config('ownership.role_column', 'owner_role'));
        }
        return null;
    }

    public function getOwnershipRecord(Model $owner): ?OwnershipModel
    {
        return $this->ownerships()
            ->where('owner_id', $owner->getKey())
            ->where('owner_type', $owner->getMorphClass())
            ->first();
    }

    public function ownerHasPermission(Model $owner, string $permission): bool
    {
        $ownership = $this->getOwnershipRecord($owner);

        if (! $ownership) {
            return false;
        }

        if ($ownership->role === 'owner') {
            return true;
        }

        $rolePermissions = config("ownership.multiple_ownership.roles.{$ownership->role}.permissions", []);

        if (in_array('*', $rolePermissions)) {
            return true;
        }

        if (in_array($permission, $rolePermissions)) {
            return true;
        }

        $customPermissions = $ownership->permissions ? json_decode($ownership->permissions, true) : [];

        return in_array($permission, $customPermissions);
    }

    protected function isValidRole(string $role): bool
    {
        $roles = config('ownership.multiple_ownership.roles', []);
        return array_key_exists($role, $roles) || in_array($role, $roles, true);
    }
}
