<?php

namespace Dibakar\Ownership\Support;

use Closure;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

class OwnershipManager
{
    public function __construct(
        protected string $guard,
        protected Closure $bypass,
        protected bool $scopeInConsole = false
    ) {}

    protected ?Model $current = null;

    public function current(): ?Model
    {
        if ($this->current) return $this->current;

        $user = Auth::guard($this->guard)->user();
        return $user instanceof Model ? $user : null;
    }

    public function set(?Model $owner): static
    {
        $this->current = $owner;
        return $this;
    }

    public function bypass(?Authenticatable $user = null): bool
    {
        $fn = $this->bypass;
        return (bool) $fn($user ?? Auth::guard($this->guard)->user());
    }

    public function shouldScope(): bool
    {
        if (app()->runningInConsole() && !$this->scopeInConsole) {
            return false;
        }
        $user = Auth::guard($this->guard)->user();
        return !$this->bypass($user) && (bool) $user;
    }

    public function as(?Model $owner, callable $callback): mixed
    {
        $prev = $this->current;
        $this->current = $owner;
        try { return $callback(); } finally { $this->current = $prev; }
    }
}
