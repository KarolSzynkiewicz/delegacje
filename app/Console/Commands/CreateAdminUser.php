<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;

class CreateAdminUser extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'user:create-admin {email=someone@someone.someone} {--password=password123}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create or update an admin user';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $email = $this->argument('email');
        $password = $this->option('password');

        // Create or update user
        $user = User::updateOrCreate(
            ['email' => $email],
            [
                'name' => 'Administrator',
                'password' => Hash::make($password),
                'email_verified_at' => now(),
            ]
        );

        $this->info("✅ User created/updated: {$user->email}");

        // Ensure administrator role exists
        $adminRole = Role::firstOrCreate(
            ['name' => 'administrator'],
            ['guard_name' => 'web']
        );

        // Assign admin role if not already assigned
        if (!$user->hasRole('administrator')) {
            $user->assignRole('administrator');
            $this->info("✅ Admin role assigned to {$user->email}");
        } else {
            $this->info("✅ User {$user->email} already has admin role");
        }

        $this->info("✅ Password: {$password}");
        $this->info("✅ Admin user ready: {$user->email}");

        return Command::SUCCESS;
    }
}
