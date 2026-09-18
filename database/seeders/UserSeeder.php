<?php

namespace Database\Seeders;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Database\Seeder;

class UserSeeder extends Seeder
{
    /**
     * Every seeded account shares the password: password
     *
     * @var array<int, array{name: string, email: string, role: UserRole}>
     */
    private const ACCOUNTS = [
        ['name' => 'Admin Gateflow', 'email' => 'admin@gateflow.test', 'role' => UserRole::Admin],
        ['name' => 'Scanner Gate 01', 'email' => 'scanner01@gateflow.test', 'role' => UserRole::Scanner],
        ['name' => 'Scanner Gate 02', 'email' => 'scanner02@gateflow.test', 'role' => UserRole::Scanner],
        ['name' => 'Scanner Gate 03', 'email' => 'scanner03@gateflow.test', 'role' => UserRole::Scanner],
        ['name' => 'Scanner Gate 04', 'email' => 'scanner04@gateflow.test', 'role' => UserRole::Scanner],
    ];

    public function run(): void
    {
        foreach (self::ACCOUNTS as $account) {
            $user = User::query()->firstOrNew(['email' => $account['email']]);

            $user->forceFill([
                'name' => $account['name'],
                'role' => $account['role'],
                'password' => 'password',
                'email_verified_at' => now(),
            ])->save();
        }
    }
}
