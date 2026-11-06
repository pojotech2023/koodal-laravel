<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Partner preferences — the right-hand side of every match calculation.
 *
 * Scalars and ranges are real columns so they can be indexed and range-queried.
 * Multi-selects ("any of these communities") are JSON arrays: modelling each as
 * a pivot table would mean ten extra tables and a ten-way join on the hottest
 * query in the product, for a set that is read whole every single time.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('user_preferences', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->unique()->constrained()->cascadeOnDelete();

            $table->unsignedTinyInteger('age_min')->nullable();
            $table->unsignedTinyInteger('age_max')->nullable();
            $table->unsignedSmallInteger('height_min_cm')->nullable();
            $table->unsignedSmallInteger('height_max_cm')->nullable();

            $table->json('marital_status_ids')->nullable();
            $table->json('religion_ids')->nullable();
            $table->json('community_ids')->nullable();
            $table->json('mother_tongue_ids')->nullable();
            $table->json('education_ids')->nullable();
            $table->json('occupation_ids')->nullable();
            $table->json('country_ids')->nullable();
            $table->json('state_ids')->nullable();
            $table->json('city_ids')->nullable();

            $table->unsignedBigInteger('income_min')->nullable();
            $table->unsignedBigInteger('income_max')->nullable();

            $table->string('diet', 30)->nullable();
            $table->string('smoking', 20)->nullable();
            $table->string('drinking', 20)->nullable();
            $table->string('manglik_preference', 20)->nullable();
            $table->string('physical_status_preference', 30)->nullable();

            $table->text('about_partner')->nullable();

            $table->timestamps();

            $table->index(['age_min', 'age_max']);
        });

        Schema::create('saved_searches', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('name', 100);
            $table->json('filters');
            $table->boolean('alert_enabled')->default(false);
            $table->timestamp('last_alerted_at')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('saved_searches');
        Schema::dropIfExists('user_preferences');
    }
};
