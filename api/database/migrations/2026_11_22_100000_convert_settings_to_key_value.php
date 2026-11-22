<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/** Settings move from one wide singleton row to key/value rows declared in config/settings.php. */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('setting_values', function (Blueprint $table) {
            $table->string('key')->primary();
            // JSON-encoded, or an encrypted payload for secrets.
            $table->text('value')->nullable();
            $table->timestamps();
        });

        $row = DB::table('settings')->first();

        if ($row !== null) {
            $now = now();
            foreach (array_keys(config('settings.settings')) as $key) {
                if (property_exists($row, $key) && $row->{$key} !== null) {
                    // jsonb columns already come back as JSON text; plain columns are encoded.
                    $value = in_array($key, ['hours', 'social_links'], true) ? $row->{$key} : json_encode($row->{$key});
                    DB::table('setting_values')->insert(['key' => $key, 'value' => $value, 'created_at' => $now, 'updated_at' => $now]);
                }
            }
        }

        Schema::drop('settings');
    }

    public function down(): void
    {
        Schema::dropIfExists('setting_values');
    }
};
