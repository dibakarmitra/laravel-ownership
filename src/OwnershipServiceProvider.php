<?php

namespace Dibakar\Ownership;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\File;
use Illuminate\Support\ServiceProvider;
use Illuminate\Database\Schema\Blueprint;
use Dibakar\Ownership\Support\OwnershipManager;

class OwnershipServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $configPath = __DIR__ . '/../config/ownership.php';

        if (File::exists($configPath)) {
            $this->mergeConfigFrom($configPath, 'ownership');
        }

        $this->app->singleton(OwnershipManager::class, function () {
            return new OwnershipManager(
                config('ownership.guard'),
                config('ownership.bypass'),
                (bool) config('ownership.scope_in_console', false)
            );
        });
    }

    public function boot(): void
    {
        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__ . '/../config/ownership.php' => config_path('ownership.php'),
            ], 'ownership-config');

            $this->publishes([
                __DIR__ . '/../config/ownership.php' => config_path('ownership.php'),
            ], 'config');

            $migrationsPath = __DIR__ . '/../database/migrations';
            if (File::isDirectory($migrationsPath)) {
                $this->publishes([
                    $migrationsPath => database_path('migrations'),
                ], 'ownership-migrations');

                $this->loadMigrationsFrom($migrationsPath);
            }
        }

        $this->registerSchemaMacros();
        $this->registerBladeDirectives();
    }

    protected function registerSchemaMacros(): void
    {
        Blueprint::macro('ownerMorphs', function (string $name = null) {
            /** @var \Illuminate\Database\Schema\Blueprint $this */
            $name = $name ?: config('ownership.morph_name', 'owner');
            $this->nullableMorphs($name);
            $this->index([$name . '_type', $name . '_id'], $name . '_index');
        });
    }

    protected function registerBladeDirectives(): void
    {
        // @owned($model) ... @endowned
        Blade::if('owned', function ($model) {
            $user = Auth::guard(config('ownership.guard'))->user();
            return $model
                && method_exists($model, 'isOwnedBy')
                && $model->isOwnedBy($user);
        });

        // @canOwn($ability, $model)
        Blade::if('canOwn', function ($ability, $model) {
            $user = Auth::guard(config('ownership.guard'))->user();
            return $user && $user->can($ability, $model);
        });

        // @isOwner($model)
        Blade::if('isOwner', function ($model) {
            $user = Auth::guard(config('ownership.guard'))->user();
            return $model
                && method_exists($model, 'isOwnedBy')
                && $model->isOwnedBy($user);
        });
    }
}
