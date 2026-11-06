<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * `$table->timestamp('x')` is NOT NULL with no default. On a MySQL server
     * running with explicit_defaults_for_timestamp=OFF — the live one — the
     * first such column in a table silently becomes
     * `DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP`, so every UPDATE
     * of the row rewrites it. For otp_verifications.expires_at that meant one
     * wrong code expired the right one; for subscriptions.starts_at, any status
     * change moved the start date.
     *
     * Each column keeps a default of now() and loses the ON UPDATE. Running it
     * on a database that never had the problem changes nothing.
     */
    protected const COLUMNS = [
        'otp_verifications' => 'expires_at',
        'subscriptions' => 'starts_at',
        'matches' => 'matched_at',
        'match_scores' => 'computed_at',
        'profile_views' => 'viewed_at',
        'match_invitations' => 'expires_at',
    ];

    public function up(): void
    {
        if (! in_array(DB::getDriverName(), ['mysql', 'mariadb'], true)) {
            return;
        }

        foreach (self::COLUMNS as $table => $column) {
            if (Schema::hasColumn($table, $column)) {
                DB::statement("ALTER TABLE `{$table}` MODIFY `{$column}` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP");
            }
        }
    }

    /** The auto-update was never wanted, so there is nothing to put back. */
    public function down(): void {}
};
