<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('user_preferences', function (Blueprint $table) {
            if (! Schema::hasColumn('user_preferences', 'weight_min_kg')) {
                $table->unsignedSmallInteger('weight_min_kg')->nullable()->after('height_max_cm');
            }
            if (! Schema::hasColumn('user_preferences', 'weight_max_kg')) {
                $table->unsignedSmallInteger('weight_max_kg')->nullable()->after('weight_min_kg');
            }
            if (! Schema::hasColumn('user_preferences', 'income_range_ids')) {
                $table->json('income_range_ids')->nullable()->after('occupation_ids');
            }
            if (! Schema::hasColumn('user_preferences', 'complexions')) {
                $table->json('complexions')->nullable()->after('city_ids');
            }
            if (! Schema::hasColumn('user_preferences', 'raasis')) {
                $table->json('raasis')->nullable()->after('complexions');
            }
            if (! Schema::hasColumn('user_preferences', 'stars')) {
                $table->json('stars')->nullable()->after('raasis');
            }
            if (! Schema::hasColumn('user_preferences', 'castes')) {
                $table->json('castes')->nullable()->after('stars');
            }
        });
    }

    public function down(): void
    {
        Schema::table('user_preferences', function (Blueprint $table) {
            $columns = array_filter(
                ['weight_min_kg', 'weight_max_kg', 'income_range_ids', 'complexions', 'raasis', 'stars', 'castes'],
                fn (string $col) => Schema::hasColumn('user_preferences', $col)
            );
            if ($columns !== []) {
                $table->dropColumn($columns);
            }
        });
    }
};
