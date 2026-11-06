<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('membership_plans', function (Blueprint $table) {
            $table->id();
            $table->string('name', 80);
            $table->string('slug', 100)->unique();
            $table->text('description')->nullable();

            $table->decimal('price', 10, 2)->default(0);
            $table->char('currency', 3)->default('INR');
            $table->unsignedSmallInteger('duration_days')->nullable();   // null = never expires (free tier)

            // The whole point of the membership system: capabilities are data,
            // read by FeatureGate. Nothing in code says `if plan === 'gold'`.
            $table->json('features');

            // Exactly one row carries this. It means everyone has a membership,
            // which removes an entire class of "user has no subscription" bugs.
            $table->boolean('is_default')->default(false);

            $table->boolean('is_active')->default(true);
            $table->boolean('is_featured')->default(false);
            $table->string('badge_text', 40)->nullable();
            $table->unsignedSmallInteger('sort_order')->default(0);

            $table->timestamps();
            $table->softDeletes();

            $table->index(['is_active', 'sort_order']);
        });

        Schema::create('payments', function (Blueprint $table) {
            $table->id();

            // nullOnDelete: a deleted user must not erase the financial record.
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('membership_plan_id')->nullable()->constrained()->nullOnDelete();
            $table->unsignedBigInteger('subscription_id')->nullable();

            $table->string('razorpay_order_id')->unique();

            // Unique is what makes webhook replay safe — a retried delivery
            // cannot create a second subscription.
            $table->string('razorpay_payment_id')->nullable()->unique();
            $table->string('razorpay_signature')->nullable();

            $table->decimal('amount', 10, 2);
            $table->char('currency', 3)->default('INR');
            $table->string('status', 20)->default('created');   // created, attempted, paid, failed, refunded
            $table->string('method', 30)->nullable();           // card, upi, netbanking, wallet
            $table->string('failure_reason')->nullable();

            $table->string('refund_id')->nullable();
            $table->decimal('refunded_amount', 10, 2)->nullable();
            $table->string('refund_reason')->nullable();

            $table->json('notes')->nullable();
            $table->timestamp('verified_at')->nullable();

            // Sequential and gap-free per financial year — an accountant will ask.
            $table->string('invoice_number', 30)->nullable()->unique();
            $table->string('invoice_path')->nullable();

            $table->timestamps();

            $table->index(['user_id', 'created_at']);
            $table->index(['status', 'created_at']);
        });

        Schema::create('subscriptions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();

            // restrictOnDelete: a plan with subscribers cannot be deleted.
            $table->foreignId('membership_plan_id')->constrained()->restrictOnDelete();

            $table->foreignId('payment_id')->nullable()->constrained()->nullOnDelete();

            $table->timestamp('starts_at');
            $table->timestamp('expires_at')->nullable();
            $table->string('status', 20)->default('pending');   // pending, active, expired, cancelled, superseded
            $table->boolean('auto_renew')->default(false);

            $table->timestamp('cancelled_at')->nullable();
            $table->string('cancellation_reason')->nullable();

            // Frozen at purchase. When an admin edits a plan, people who
            // already paid keep exactly what they bought — without this,
            // repricing silently takes away what thousands of users purchased.
            $table->json('features_snapshot');

            // Each reminder fires at most once.
            $table->json('reminders_sent')->nullable();

            $table->timestamps();

            $table->index(['user_id', 'status']);
            $table->index(['status', 'expires_at']);
        });

        Schema::table('payments', function (Blueprint $table) {
            $table->foreign('subscription_id')->references('id')->on('subscriptions')->nullOnDelete();
        });

        Schema::create('usage_counters', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('key', 60);              // interests_per_month, …
            $table->string('period', 10);           // 2026-08 (monthly) or 2026-08-13 (daily)
            $table->unsignedInteger('used')->default(0);
            $table->timestamps();

            // Upserted with an atomic increment, so two concurrent requests
            // cannot both consume the last unit of a quota.
            $table->unique(['user_id', 'key', 'period']);
        });
    }

    public function down(): void
    {
        Schema::table('payments', function (Blueprint $table) {
            $table->dropForeign(['subscription_id']);
        });

        foreach (['usage_counters', 'subscriptions', 'payments', 'membership_plans'] as $table) {
            Schema::dropIfExists($table);
        }
    }
};
