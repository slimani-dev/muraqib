<?php

namespace Database\Seeders;

use App\Actions\Teams\CreateTeam;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class DatabaseSeeder extends Seeder
{
    /**
     * Create the first admin, the only way in on a fresh install (there's no public sign-up).
     * Uses ADMIN_EMAIL / ADMIN_PASSWORD from .env; without a password a random one is
     * generated and printed once. Running it again leaves an existing admin untouched.
     */
    public function run(): void
    {
        $email = config('app.admin.email');

        if (User::query()->where('email', $email)->exists()) {
            $this->command?->info("Admin {$email} already exists; nothing to do.");

            return;
        }

        $password = config('app.admin.password') ?: Str::password(20, symbols: false);

        $admin = User::query()->create([
            'name' => config('app.admin.name'),
            'email' => $email,
            'password' => $password,
            'is_admin' => true,
            'email_verified_at' => now(),
        ]);

        app(CreateTeam::class)->handle($admin, "{$admin->name}'s Team", isPersonal: true);

        $this->command?->info("Admin created: {$email}");

        if (! config('app.admin.password')) {
            $this->command?->warn("Generated password (shown once, change it after logging in): {$password}");
        }
    }
}
