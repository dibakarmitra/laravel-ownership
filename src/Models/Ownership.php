<?php

declare(strict_types=1);

namespace Dibakar\Ownership\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Str;

class Ownership extends Model
{
    use HasFactory;

    protected $fillable = [
        'ownable_id',
        'ownable_type',
        'owner_id',
        'owner_type',
        'role',
        'permissions',
    ];

    protected $casts = [
        'permissions' => 'array',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    protected $appends = [
        'owner_name',
        'ownable_name',
    ];

    public function getTable()
    {
        return Config::get('ownership.multiple_ownership.table_name', parent::getTable());
    }

    public function ownable(): MorphTo
    {
        return $this->morphTo();
    }

    public function owner(): MorphTo
    {
        return $this->morphTo('owner');
    }

    public function getOwnerNameAttribute(): ?string
    {
        if (! $this->relationLoaded('owner')) {
            return null;
        }

        return $this->owner?->name ?? $this->owner?->email ?? $this->owner?->getKey();
    }

    public function getOwnableNameAttribute(): ?string
    {
        if (! $this->relationLoaded('ownable')) {
            return null;
        }

        if ($this->ownable === null) {
            return null;
        }

        if (method_exists($this->ownable, 'getOwnableName')) {
            return $this->ownable->getOwnableName();
        }

        if (isset($this->ownable->name)) {
            return $this->ownable->name;
        }

        if (isset($this->ownable->title)) {
            return $this->ownable->title;
        }

        return Str::title(Str::singular($this->ownable->getTable())) . ' #' . $this->ownable->getKey();
    }

    public function scopeWithRole($query, string $role)
    {
        return $query->where('role', $role);
    }

    public function scopeForOwner($query, Model $owner)
    {
        return $query->where('owner_id', $owner->getKey())
                    ->where('owner_type', $owner->getMorphClass());
    }

    public function hasPermission(string $permission): bool
    {
        if ($this->role === 'owner') {
            return true;
        }

        $rolePermissions = Config::get("ownership.multiple_ownership.roles.{$this->role}.permissions", []);

        if (in_array('*', $rolePermissions)) {
            return true;
        }

        if (in_array($permission, $rolePermissions)) {
            return true;
        }

        $customPermissions = $this->permissions ?? [];
        
        return in_array($permission, $customPermissions);
    }
}
