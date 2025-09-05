<?php

namespace Dibakar\Ownership\Database\Migrations;

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('ownerships', function (Blueprint $table) {
            $table->id();
            $table->morphs('ownable');
            $table->morphs('owner');
            $table->string('role')->nullable()->comment('Optional role for the owner (e.g., "admin", "editor")');
            $table->json('permissions')->nullable()->comment('Optional JSON field for granular permissions');
            $table->timestamps();
            
            // Add composite unique index to prevent duplicate ownerships
            $table->unique(['ownable_id', 'ownable_type', 'owner_id', 'owner_type'], 'ownership_unique');
        });
    }

    public function down()
    {
        Schema::dropIfExists('ownerships');
    }
};
