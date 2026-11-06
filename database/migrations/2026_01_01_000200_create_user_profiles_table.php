<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Deliberately wide. A section-per-table design would mean six joins on the
 * endpoint that runs on nearly every screen, for no normalisation benefit —
 * these are all 1:1 attributes of one profile.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('user_profiles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->unique()->constrained()->cascadeOnDelete();

            // --- Basic --------------------------------------------------------
            // A parent or sibling often owns the account; the UI copy and some
            // permissions branch on this, so it is a first-class field.
            $table->string('profile_created_by', 20)->default('self');  // self, parent, sibling, relative, friend

            $table->unsignedSmallInteger('height_cm')->nullable();
            $table->unsignedSmallInteger('weight_kg')->nullable();
            $table->foreignId('marital_status_id')->nullable()->constrained()->nullOnDelete();
            $table->string('body_type', 30)->nullable();
            $table->string('complexion', 30)->nullable();
            $table->string('physical_status', 30)->default('normal');
            $table->unsignedTinyInteger('children_count')->nullable();
            $table->string('children_living_status', 20)->nullable();

            // --- Religious ----------------------------------------------------
            $table->foreignId('religion_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('community_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('sub_community_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('mother_tongue_id')->nullable()->constrained()->nullOnDelete();
            $table->string('caste', 80)->nullable();
            $table->string('gothra', 80)->nullable();
            $table->string('star', 60)->nullable();
            $table->string('raasi', 60)->nullable();
            $table->string('manglik', 20)->nullable();          // yes, no, dont_know
            $table->string('horoscope_path')->nullable();
            $table->time('birth_time')->nullable();
            $table->string('birth_place', 120)->nullable();

            // --- Education ----------------------------------------------------
            $table->foreignId('education_id')->nullable()->constrained('educations')->nullOnDelete();
            $table->string('education_detail', 150)->nullable();
            $table->string('college', 150)->nullable();
            $table->string('university', 150)->nullable();
            $table->string('specialization', 150)->nullable();

            // --- Professional -------------------------------------------------
            $table->foreignId('occupation_id')->nullable()->constrained()->nullOnDelete();
            $table->string('company', 150)->nullable();
            $table->string('job_type', 30)->nullable();         // govt, private, business, self_employed, defence, not_working
            $table->foreignId('income_range_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('work_country_id')->nullable()->constrained('countries')->nullOnDelete();
            $table->foreignId('work_state_id')->nullable()->constrained('states')->nullOnDelete();
            $table->foreignId('work_city_id')->nullable()->constrained('cities')->nullOnDelete();

            // --- Location -----------------------------------------------------
            $table->foreignId('country_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('state_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('city_id')->nullable()->constrained()->nullOnDelete();
            $table->string('residency_status', 40)->nullable();
            $table->string('pincode', 10)->nullable();

            // --- Family -------------------------------------------------------
            $table->string('father_occupation', 120)->nullable();
            $table->string('mother_occupation', 120)->nullable();
            $table->unsignedTinyInteger('brothers_count')->nullable();
            $table->unsignedTinyInteger('brothers_married')->nullable();
            $table->unsignedTinyInteger('sisters_count')->nullable();
            $table->unsignedTinyInteger('sisters_married')->nullable();
            $table->string('family_type', 30)->nullable();      // joint, nuclear
            $table->string('family_status', 30)->nullable();    // middle_class, upper_middle, rich
            $table->string('family_values', 30)->nullable();    // traditional, moderate, liberal
            $table->string('family_location', 120)->nullable();
            $table->text('family_description')->nullable();

            // --- Lifestyle ----------------------------------------------------
            $table->string('diet', 30)->nullable();
            $table->string('smoking', 20)->nullable();
            $table->string('drinking', 20)->nullable();

            // JSON is right here and only here: display-only multi-selects that
            // are never filtered or sorted on.
            $table->json('hobbies')->nullable();
            $table->json('interests')->nullable();

            // --- Free text ----------------------------------------------------
            $table->text('about_me')->nullable();
            $table->text('expectations')->nullable();

            // --- Derived / denormalised --------------------------------------
            $table->unsignedTinyInteger('completion_percentage')->default(0);
            $table->boolean('is_verified')->default(false);
            $table->timestamp('verified_at')->nullable();
            $table->string('approval_status', 20)->default('pending');  // pending, approved, rejected
            $table->string('rejection_reason')->nullable();
            $table->foreignId('primary_photo_id')->nullable();          // FK added after profile_photos exists

            // Maintained in the action that causes the change, inside the same
            // transaction — counting these on read does not scale.
            $table->unsignedInteger('profile_views_count')->default(0);
            $table->unsignedInteger('shortlisted_count')->default(0);
            $table->unsignedInteger('interests_received_count')->default(0);

            $table->timestamps();
            $table->softDeletes();

            // Search indexes, ordered to match the real filter combinations.
            $table->index(['religion_id', 'community_id', 'mother_tongue_id'], 'idx_search_core');
            $table->index(['country_id', 'state_id', 'city_id'], 'idx_search_location');
            $table->index(['education_id', 'occupation_id', 'income_range_id'], 'idx_search_ec');
            $table->index('marital_status_id', 'idx_marital');
            $table->index(['approval_status', 'is_verified'], 'idx_approval');
            $table->index('completion_percentage', 'idx_completion');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('user_profiles');
    }
};
