<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (! Schema::hasColumn('users', 'aadhaar_number')) {
                $table->string('aadhaar_number', 20)->nullable()->after('email');
            }
            if (! Schema::hasColumn('users', 'is_aadhaar_verified')) {
                $table->boolean('is_aadhaar_verified')->default(false)->after('aadhaar_number');
            }
            if (! Schema::hasColumn('users', 'aadhaar_verified_at')) {
                $table->timestamp('aadhaar_verified_at')->nullable()->after('is_aadhaar_verified');
            }
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $columns = [];
            if (Schema::hasColumn('users', 'aadhaar_number')) {
                $columns[] = 'aadhaar_number';
            }
            if (Schema::hasColumn('users', 'is_aadhaar_verified')) {
                $columns[] = 'is_aadhaar_verified';
            }
            if (Schema::hasColumn('users', 'aadhaar_verified_at')) {
                $columns[] = 'aadhaar_verified_at';
            }
            if (! empty($columns)) {
                $table->dropColumn($columns);
            }
        });
    }
};

