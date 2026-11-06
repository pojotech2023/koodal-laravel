<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('matrimony_limits', function (Blueprint $table) {
            $table->string('requested_plan', 40)->nullable()->after('plan_type');
            $table->timestamp('requested_at')->nullable()->after('requested_plan');
        });
    }

    public function down(): void
    {
        Schema::table('matrimony_limits', function (Blueprint $table) {
            $table->dropColumn(['requested_plan', 'requested_at']);
        });
    }
};
