# Laravel Ownership

[![Latest Version on Packagist](https://img.shields.io/packagist/v/dibakar/laravel-ownership.svg?style=flat-square)](https://packagist.org/packages/dibakar/laravel-ownership)
[![Total Downloads](https://img.shields.io/packagist/dt/dibakar/laravel-ownership.svg?style=flat-square)](https://packagist.org/packages/dibakar/laravel-ownership)
[![PHP Version](https://img.shields.io/packagist/php-v/dibakar/laravel-ownership.svg?style=flat-square)](https://packagist.org/packages/dibakar/laravel-ownership)
[![License](https://img.shields.io/packagist/l/dibakar/laravel-ownership?style=flat-square)](https://packagist.org/packages/dibakar/laravel-ownership)
[![Tests](https://github.com/dibakar/laravel-ownership/actions/workflows/run-tests.yml/badge.svg)](https://github.com/dibakar/laravel-ownership/actions)
[![Code Style](https://github.styleci.io/repos/123456789/shield?branch=main)](https://github.styleci.io/repos/123456789)

A comprehensive ownership management system for Laravel applications. This package provides an elegant way to handle both single and multiple ownership scenarios with role-based permissions, events, and query scopes.

## Features

- **Dual Ownership Modes**: Support for both single and multiple ownership models
- **Role-based Access Control**: Define custom roles with specific permissions
- **Flexible Configuration**: Highly customizable to fit any application needs
- **Event-driven Architecture**: Built-in events for all ownership changes
- **Powerful Query Scopes**: Filter models by ownership with ease
- **Performance Optimized**: Built-in caching for ownership checks
- **Type Safety**: Strict type declarations and modern PHP features
- **Laravel Integration**: Seamless integration with Laravel's authentication system
- **Morphable Owners**: Support for any authenticatable model as an owner
- **Bulk Operations**: Manage multiple owners at once with sync methods

## Installation

1. Install the package via Composer:

```bash
composer require dibakar/laravel-ownership
```

2. Publish the configuration file:

```bash
php artisan vendor:publish --provider="Dibakar\\Ownership\\OwnershipServiceProvider" --tag=config
```

3. Publish and run the migrations:

```bash
php artisan vendor:publish --provider="Dibakar\\Ownership\\OwnershipServiceProvider" --tag=migrations
php artisan migrate
```

## Configuration

The configuration file (`config/ownership.php`) allows you to customize various aspects of the package. Here are the main configuration options:

```php
return [
    /*
    |--------------------------------------------------------------------------
    | Morph Name
    |--------------------------------------------------------------------------
    |
    | This is the name of the polymorphic relationship used for ownership.
    | You can change this to 'user', 'team', 'organization', etc.
    */
    'morph_name' => 'owner',

    /*
    |--------------------------------------------------------------------------
    | Global Scope
    |--------------------------------------------------------------------------
    |
    | When enabled, a global scope will be applied to automatically scope
    | queries to the current owner in single ownership mode.
    */
    'apply_global_scope' => true,

    /*
    |--------------------------------------------------------------------------
    | Authentication Guard
    |--------------------------------------------------------------------------
    |
    | The authentication guard used to retrieve the currently authenticated user.
    */
    'guard' => 'web',

    /*
    |--------------------------------------------------------------------------
    | Ownership Mode
    |--------------------------------------------------------------------------
    |
    | Set to 'single' for one owner per model or 'multiple' for many owners.
    */
    'mode' => 'single',

    /*
    |--------------------------------------------------------------------------
    | Cache Configuration
    |--------------------------------------------------------------------------
    */
    'cache' => [
        'enabled' => true,
        'ttl' => 3600, // Cache time-to-live in seconds
    ],

    /*
    |--------------------------------------------------------------------------
    | Multiple Ownership Configuration
    |--------------------------------------------------------------------------
    */
    'multiple_ownership' => [
        'table_name' => 'ownerships',
        'roles' => [
            'owner' => [
                'display_name' => 'Owner',
                'permissions' => ['*'], // Wildcard means all permissions
            ],
            'editor' => [
                'display_name' => 'Editor',
                'permissions' => ['edit', 'view'],
            ],
            'viewer' => [
                'display_name' => 'Viewer',
                'permissions' => ['view'],
            ],
        ],
        'default_role' => 'viewer',
    ],
];
```

## Usage

### Single Ownership Mode

Add the `HasOwnership` trait to your model for single ownership:

```php
use Illuminate\Database\Eloquent\Model;
use Dibakar\Ownership\Traits\HasOwnership;

class Post extends Model
{
    use HasOwnership;
    
    // ...
}
```

#### Basic Operations

```php
// Creating a new post with the current user as owner
$post = Post::create([
    'title' => 'My First Post',
    'content' => 'This is my first post.'
]);

// Explicitly set the owner
$post->setOwner($user);

// Check ownership
if ($post->isOwnedBy($user)) {
    // User owns the post
}

// Get the owner
$owner = $post->owner;

// Transfer ownership
$post->transferOwnership($currentOwner, $newOwner);

// Clear the owner
$post->clearOwner();
```

### Multiple Ownership Mode

First, update your config to use multiple ownership mode:

```php
// config/ownership.php
return [
    'mode' => 'multiple',
    // ... rest of the config
];
```

Then add the `HasOwnership` trait to your model:

```php
use Illuminate\Database\Eloquent\Model;
use Dibakar\Ownership\Traits\HasOwnership;

class Project extends Model
{
    use HasOwnership;
    
    // ...
}
```

#### Managing Multiple Owners

```php
// Add an owner with a specific role
$project->addOwner($user, 'owner');

// Add multiple owners at once
$project->addOwners([$user1, $user2, $user3], 'editor', ['custom_permission']);

// Remove an owner
$project->removeOwner($user);

// Check if a user is an owner
if ($project->hasOwner($user)) {
    // User is an owner
}

// Get all owners with their roles
$owners = $project->getOwners();

// Get owners with a specific role
$editors = $project->getOwnersWithRole('editor');

// Check if a user has a specific role
if ($project->hasOwnerWithRole($user, 'admin')) {
    // User has admin role
}

// Update a user's role
$project->updateOwnerRole($user, 'admin');

// Sync owners (removes any owners not in the list)
$project->syncOwners([
    $user1,
    $user2,
    $user3,
], 'owner');

// Clear all owners
$project->clearAllOwners();
}

### Query Scopes

The package provides several query scopes to filter models by ownership:

```php
// Get all posts owned by the current user
$posts = Post::ownedByCurrent()->get();

// Get all posts owned by a specific user
$userPosts = Post::ownedBy($user)->get();

// In multiple ownership mode, get projects where user has a specific role
$projects = Project::whereHasOwnerWithRole($user, 'editor')->get();

// Get models where user has specific permission
$editablePosts = Post::whereUserHasPermission($user, 'edit')->get();
```

### Events

The package dispatches events when ownership changes occur:

```php
use Dibakar\Ownership\Events\OwnershipCreated;
use Dibakar\Ownership\Events\OwnershipUpdated;
use Dibakar\Ownership\Events\OwnershipDeleted;
use Dibakar\Ownership\Events\OwnershipTransferred;

// Listen for ownership events
Event::listen(OwnershipCreated::class, function (OwnershipCreated $event) {
    $model = $event->model;
    $owner = $event->owner;
    $role = $event->role;
    
    // Handle the event
});
```

### Middleware

Protect your routes with the ownership middleware:

```php
// routes/web.php
Route::middleware(['auth', 'ownership:post,edit'])
    ->group(function () {
        Route::get('/posts/{post}/edit', 'PostController@edit');
        Route::put('/posts/{post}', 'PostController@update');
    });

// With custom ownership check
Route::middleware(['auth', 'ownership:post,edit,App\Policies\CustomPostPolicy'])
    ->group(function () {
        // Your routes
    });
```

### Blade Directives

```blade
{{-- Check if current user owns the model --}}
@owned($post)
    <a href="{{ route('posts.edit', $post) }}">Edit</a>
@endowned

{{-- Check specific user ownership --}}
@owned($post, $specificUser)
    <span>Owned by {{ $specificUser->name }}</span>
@endowned

{{-- Check if user has specific permission --}}
@can('edit', $post)
    <a href="{{ route('posts.edit', $post) }}">Edit</a>
@endcan

@owned($model, $user = null)
    This content is only visible to the owner
@endowned

@canOwn($model, 'edit', $user = null)
    This content is only visible to users with edit permission
@endCanOwn

@isOwner($model, $user = null)
    This content is only visible to an owner
@endIsOwner
```

### Events

The package dispatches several events that you can listen to:

- `Dibakar\Ownership\Events\OwnershipCreated`
- `Dibakar\Ownership\Events\OwnershipUpdated`
- `Dibakar\Ownership\Events\OwnershipDeleted`
- `Dibakar\Ownership\Events\OwnershipTransferred`

Example listener:

```php
namespace App\Listeners;

use Dibakar\Ownership\Events\OwnershipCreated;

class LogOwnershipCreated
{
    public function handle(OwnershipCreated $event)
    {
        // Handle the event
    }
}
```

## Testing

Run the tests with:

```bash
composer test
```

## Security

If you discover any security related issues, please email dibakarmitra07@gmail.com instead of using the issue tracker.

## License

The MIT License (MIT). Please see [License File](LICENSE) for more information.

## Contributing

Please see [CONTRIBUTING](CONTRIBUTING.md) for details.

## Credits

- [Dibakar Mitra](https://github.com/dibakarmitra)
- [All Contributors](../../contributors)
