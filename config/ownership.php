<?php

use Illuminate\Contracts\Auth\Authenticatable;

return [
    /*
    |--------------------------------------------------------------------------
    | Single Ownership Configuration
    |--------------------------------------------------------------------------
    |
    | These settings control the behavior of the single ownership implementation.
    |
    */

    /*
    |--------------------------------------------------------------------------
    | Morph Name
    |--------------------------------------------------------------------------
    |
    | This is the name of the polymorphic relationship used for ownership.
    | You can change this to 'user', 'team', 'organization', etc. based on your needs.
    |
    */
    'morph_name' => 'owner',

    /*
    |--------------------------------------------------------------------------
    | Global Scope
    |--------------------------------------------------------------------------
    |
    | When enabled, a global scope will be applied to all models using the
    | Ownable trait, automatically scoping queries to the current owner.
    |
    */
    'apply_global_scope' => true,

    /*
    |--------------------------------------------------------------------------
    | Authentication Guard
    |--------------------------------------------------------------------------
    |
    | This is the authentication guard that will be used to retrieve the
    | currently authenticated user for ownership checks.
    |
    */
    'guard' => 'web',

    /*
    |--------------------------------------------------------------------------
    | Bypass Configuration
    |--------------------------------------------------------------------------
    |
    | Define a closure that determines if the current user can bypass ownership
    | checks. This is useful for super admins or users with special privileges.
    |
    */
    'bypass' => static function (?Authenticatable $user): bool {
        return $user && method_exists($user, 'can') && $user->can('ownership.bypass');
    },

    /*
    |--------------------------------------------------------------------------
    | Console Scope
    |--------------------------------------------------------------------------
    |
    | Determines whether to apply ownership scoping in console commands.
    | Typically disabled for seeds, queues, and other background tasks.
    |
    */
    'scope_in_console' => false,

    /*
    |--------------------------------------------------------------------------
    | Cache Configuration
    |--------------------------------------------------------------------------
    |
    | Configure caching for ownership checks to improve performance.
    |
    */
    'cache' => [
        'enabled' => true,
        'ttl' => 3600, // Cache time-to-live in seconds
        'prefix' => 'ownership_',
    ],

    /*
    |--------------------------------------------------------------------------
    | Events
    |--------------------------------------------------------------------------
    |
    | Enable or disable events that are fired when ownership changes.
    |
    */
    'events' => [
        'ownership_created' => true,
        'ownership_updated' => true,
        'ownership_deleted' => true,
        'ownership_transferred' => true,
    ],

    /*
    |--------------------------------------------------------------------------
    | Multiple Ownership Configuration
    |--------------------------------------------------------------------------
    |
    | These settings control the behavior of the multiple ownership implementation.
    |
    */
    'multiple_ownership' => [
        /*
        |--------------------------------------------------------------------------
        | Table Name
        |--------------------------------------------------------------------------
        |
        | This is the name of the pivot table that will store the ownership relationships.
        |
        */
        'table_name' => 'ownerships',
        
        /*
        |--------------------------------------------------------------------------
        | Default Role
        |--------------------------------------------------------------------------
        |
        | The default role assigned to a new owner when no specific role is provided.
        |
        */
        'default_role' => 'owner',
        
        /*
        |--------------------------------------------------------------------------
        | Available Roles
        |--------------------------------------------------------------------------
        |
        | Define the available roles and their permissions. The '*' wildcard means
        | all permissions are granted. You can add or modify roles as needed.
        |
        */
        'roles' => [
            'owner' => [
                'name' => 'Owner',
                'description' => 'Full access to the resource',
                'permissions' => ['*'], // Wildcard means all permissions
            ],
            'admin' => [
                'name' => 'Administrator',
                'description' => 'Can manage all aspects except ownership',
                'permissions' => ['view', 'edit', 'delete', 'manage_users'],
            ],
            'editor' => [
                'name' => 'Editor',
                'description' => 'Can view and edit content',
                'permissions' => ['view', 'edit'],
            ],
            'viewer' => [
                'name' => 'Viewer',
                'description' => 'Can only view content',
                'permissions' => ['view'],
            ],
        ],
        
        /*
        |--------------------------------------------------------------------------
        | Auto Assign Creator
        |--------------------------------------------------------------------------
        |
        | When enabled, the package will automatically assign ownership to the
        | creator of a resource when it's created.
        |
        */
        'auto_assign_creator' => true,

        /*
        |--------------------------------------------------------------------------
        | Ownership Validation
        |--------------------------------------------------------------------------
        |
        | Configure validation rules for ownership assignments.
        |
        */
        'validation' => [
            'max_owners' => null, // null for unlimited, or set a maximum number of owners
            'unique_owner' => true, // Whether an owner can only be assigned once
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Model Namespace
    |--------------------------------------------------------------------------
    |
    | The namespace where your models are located. This is used for resolving
    | model classes from the database.
    |
    */
    'models_namespace' => 'App\\Models',
];
