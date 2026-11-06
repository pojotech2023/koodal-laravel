<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Caste and gothra were free-text inputs (UserProfile::caste / ::gothra
 * remain plain VARCHAR — no FK added here, same as diet/smoking/drinking in
 * `lifestyle_options`). This only gives admins a managed list to drive the
 * dropdown from, mirroring the "simple" lookup shape in
 * 2026_01_01_000100_create_master_data_tables.php.
 */
return new class extends Migration
{
    public function up(): void
    {
        $simple = function (string $name) {
            Schema::create($name, function (Blueprint $table) {
                $table->id();
                $table->string('name', 120);
                $table->string('slug', 140)->unique();
                $table->unsignedSmallInteger('sort_order')->default(0);
                $table->boolean('is_active')->default(true);
                $table->timestamps();

                $table->index(['is_active', 'sort_order']);
            });
        };

        $simple('castes');
        $simple('gothras');
    }

    public function down(): void
    {
        Schema::dropIfExists('gothras');
        Schema::dropIfExists('castes');
    }
};
