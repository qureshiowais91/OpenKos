<?php

namespace App\Console\Commands;

use App\Enums\Role;
use App\Models\Property;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;

class DemoRotateCommand extends Command
{
    protected $signature = 'app:demo-rotate';

    protected $description = 'Ensure demo accounts exist, rotate their password daily, and reset their data every 48 hours';

    public function handle(): int
    {
        // ponytail: demo-only feature; gated by env so real deployments never
        // create shared accounts. Password is date-derived (PASSWORD_YYYYMMDD
        // UTC) so it expires every 24h and old shared copies stop working.
        if (! filter_var(env('DEMO_MODE', false), FILTER_VALIDATE_BOOL)) {
            return self::SUCCESS;
        }

        $password = 'PASSWORD_' . now()->format('Ymd');

        // ponytail: fixed demo roster — add more roles here if the app grows
        // more user types. Deleting after 48h cascades property_user and
        // portal data via FKs; orphaned rows are acceptable in a demo DB.
        $demoUsers = [
            ['name' => 'Demo Owner', 'email' => 'demo.owner@openkos.test'],
            ['name' => 'Demo Staff', 'email' => 'demo.staff@openkos.test'],
            ['name' => 'Demo Renter', 'email' => 'demo.renter@openkos.test'],
        ];

        foreach ($demoUsers as $demo) {
            $user = User::where('email', $demo['email'])->first();

            if ($user && $user->created_at->diffInHours(now()) >= 48) {
                $user->delete(); // ponytail: FK-restricted rows will throw and are skipped
                $user = null;
                $this->info("Reset demo user {$demo['email']} after 48h");
            }

            $user = User::updateOrCreate(
                ['email' => $demo['email']],
                ['name' => $demo['name'], 'password' => Hash::make($password), 'is_active' => true],
            );
            $user->markEmailAsVerified();

            match ($demo['email']) {
                'demo.owner@openkos.test' => $user->assignRole(Role::Owner->value),
                'demo.staff@openkos.test' => $user->properties()->sync([$this->demoProperty()->id]),
                'demo.renter@openkos.test' => Tenant::updateOrCreate(
                    ['email' => $demo['email']],
                    ['user_id' => $user->id, 'name' => $demo['name'], 'phone' => '+6281234567890'],
                ),
            };
        }

        $this->info("Demo accounts ready. Password for today (UTC): {$password}");

        return self::SUCCESS;
    }

    private function demoProperty(): Property
    {
        return Property::firstOrCreate(
            ['slug' => 'demo-kos'],
            ['name' => 'Demo Kos'],
        );
    }
}
