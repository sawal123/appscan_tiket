<?php

namespace Database\Seeders;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;
use RuntimeException;

/**
 * Seeds only the production administrator account.
 *
 * Unlike {@see DatabaseSeeder}, this seeder never creates demo events,
 * categories, tickets, or scanner accounts. Run it explicitly:
 *
 *     php artisan db:seed --class=ProductionSeeder --force
 *
 * Credentials are read from ADMIN_NAME, ADMIN_EMAIL and ADMIN_PASSWORD
 * (see config/admin.php). ADMIN_EMAIL is required; when ADMIN_PASSWORD is
 * empty a strong random password is generated and printed once.
 */
class ProductionSeeder extends Seeder
{
    public function run(): void
    {
        $email = config('admin.email');

        if (blank($email)) {
            throw new RuntimeException('Set ADMIN_EMAIL in the environment before running ProductionSeeder.');
        }

        $configuredPassword = config('admin.password');
        $user = User::query()->firstOrNew(['email' => $email]);
        $isNew = ! $user->exists;
        $generatedPassword = null;

        $user->forceFill([
            'name' => config('admin.name'),
            'role' => UserRole::Admin,
            'is_active' => true,
            'email_verified_at' => $user->email_verified_at ?? now(),
        ]);

        // Only touch the password for a brand new account, or when one is
        // supplied explicitly, so re-running this seeder can never reset the
        // password of a live admin account.
        if ($isNew || filled($configuredPassword)) {
            if (blank($configuredPassword)) {
                $generatedPassword = Str::password(16);
                $user->password = $generatedPassword;
            } else {
                $user->password = $configuredPassword;
            }
        }

        $user->save();

        $this->command->info("Admin account ready: {$user->email}");

        if ($generatedPassword !== null) {
            $this->command->warn("Generated password: {$generatedPassword}");
            $this->command->warn('Save it now and change it after the first login.');
        }
    }
}
