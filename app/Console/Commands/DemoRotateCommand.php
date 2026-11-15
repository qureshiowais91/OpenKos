<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;

class DemoRotateCommand extends Command
{
    private const DEMO_EMAILS = [
        'budi@grihaflow.com',        // owner (OwnerSeeder)
        'demo.staff@grihaflow.com',  // staff role + permissions (DemoUsersSeeder)
        'demo.tenant@grihaflow.com', // tenant with leases (DemoUsersSeeder)
    ];

    protected $signature = 'app:demo-rotate';

    protected $description = "Rotate the shared demo accounts to today's password";

    public function handle(): int
    {
        // ponytail: demo-only feature; gated by env so real deployments never
        // touch shared accounts. Password is date-derived (PASSWORD_YYYYMMDD
        // UTC) so it expires every 24h. Accounts are built by the native demo
        // seeders (db:seed at boot); this command only rotates passwords.
        if (! filter_var(env('DEMO_MODE', false), FILTER_VALIDATE_BOOL)) {
            return self::SUCCESS;
        }

        $password = 'PASSWORD_' . now()->format('Ymd');
        $found = 0;

        foreach (self::DEMO_EMAILS as $email) {
            $user = User::query()->where('email', $email)->first();

            if (! $user) {
                $this->warn("Demo user {$email} missing — run 'php artisan db:seed --force' first");

                continue;
            }

            $user->forceFill(['password' => Hash::make($password)]);
            $user->email_verified_at ??= now();
            $user->is_active = true;
            $user->save();
            $found++;
        }

        if ($found > 0) {
            $this->info("Demo accounts ready. Password for today (UTC): {$password}");
        }

        return self::SUCCESS;
    }
}
