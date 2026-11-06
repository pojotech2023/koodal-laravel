<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Every lookup the product filters on. All of it is admin-editable and served
 * by /api/v1/master-data — no client may hardcode any of these values.
 *
 * Deliberately VARCHAR + application allow-list rather than MySQL ENUM: adding
 * a religion must not require an ALTER TABLE on a million-row profile table.
 */
return new class extends Migration
{
    public function up(): void
    {
        $simple = function (string $name, ?callable $extra = null) {
            Schema::create($name, function (Blueprint $table) use ($extra) {
                $table->id();
                $table->string('name', 120);
                $table->string('slug', 140)->unique();
                $table->unsignedSmallInteger('sort_order')->default(0);
                $table->boolean('is_active')->default(true);

                if ($extra) {
                    $extra($table);
                }

                $table->timestamps();

                $table->index(['is_active', 'sort_order']);
            });
        };

        $simple('religions');

        $simple('communities', fn (Blueprint $t) => $t->foreignId('religion_id')->constrained()->cascadeOnDelete());
        $simple('sub_communities', fn (Blueprint $t) => $t->foreignId('community_id')->constrained()->cascadeOnDelete());

        $simple('mother_tongues');

        // `category` orders education by level so "adjacent tier" scoring in the
        // match algorithm has something to compare against.
        $simple('educations', fn (Blueprint $t) => $t->string('category', 40)->default('graduate'));
        $simple('occupations', fn (Blueprint $t) => $t->string('category', 40)->nullable());
        $simple('marital_statuses');

        Schema::create('income_ranges', function (Blueprint $table) {
            $table->id();
            $table->string('name', 80);            // "₹5-10 Lakh"
            $table->string('slug', 100)->unique();
            $table->unsignedBigInteger('min_amount');
            $table->unsignedBigInteger('max_amount')->nullable();   // null = "and above"
            $table->char('currency', 3)->default('INR');
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        // One table for diet / smoking / drinking / body_type / complexion.
        // They share a shape and are always fetched together.
        Schema::create('lifestyle_options', function (Blueprint $table) {
            $table->id();
            $table->string('type', 30);            // diet, smoking, drinking, body_type, complexion
            $table->string('name', 80);
            $table->string('slug', 100);
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->unique(['type', 'slug']);
            $table->index(['type', 'is_active']);
        });
    }

    public function down(): void
    {
        foreach ([
            'lifestyle_options', 'income_ranges', 'marital_statuses', 'occupations',
            'educations', 'mother_tongues', 'sub_communities', 'communities', 'religions',
        ] as $table) {
            Schema::dropIfExists($table);
        }
    }
};
