<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Pin the regional settings rows to the India-only configuration from
 * config/regional.php.
 *
 * This normalizes application configuration only — it is not a financial data
 * migration. Historical financial records (leases, invoices, payments,
 * expenses, rates) keep their own per-row currency values and are never
 * converted here.
 */
return new class extends Migration
{
    private const INDIA_SETTINGS = [
        'country_code' => 'IN',
        'locale' => 'en',
        'currency' => 'INR',
        'timezone' => 'Asia/Kolkata',
    ];

    public function up(): void
    {
        foreach (self::INDIA_SETTINGS as $key => $value) {
            DB::table('settings')->updateOrInsert(
                ['key' => $key],
                ['value' => $value, 'type' => 'string', 'updated_at' => now(), 'created_at' => now()],
            );
        }

        // The multi-currency whitelist is dead configuration for an
        // India-only application; drop the stored row.
        DB::table('settings')->where('key', 'supported_currencies')->delete();
    }

    public function down(): void
    {
        // Regional values are application-wide constants; there is no
        // meaningful rollback target.
    }
};
