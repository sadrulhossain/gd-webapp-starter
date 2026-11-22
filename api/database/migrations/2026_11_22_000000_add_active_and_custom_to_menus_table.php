<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('menus', function (Blueprint $table) {
            // Hidden entries (and their children) leave /me/menus; the routes stay protected by permissions.
            $table->boolean('active')->default(true);
            // Created through the API; only these can have their path or permission edited, or be deleted.
            $table->boolean('custom')->default(false);
        });
    }

    public function down(): void
    {
        Schema::table('menus', function (Blueprint $table) {
            $table->dropColumn(['active', 'custom']);
        });
    }
};
