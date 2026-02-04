<?php
// Script to create admin user - run this in Railway tinker
// railway run bash -c 'php artisan tinker < create_admin.php'
// OR copy-paste the code below into Railway tinker

use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;

$email = 'someone@someone.someone';
$password = 'password123';

// Create or update user
$user = User::updateOrCreate(
    ['email' => $email],
    [
        'name' => 'Administrator',
        'password' => Hash::make($password),
        'email_verified_at' => now(),
    ]
);

echo "✅ User created/updated: {$user->email}\n";

// Ensure administrator role exists
$adminRole = Role::firstOrCreate(
    ['name' => 'administrator'],
    ['guard_name' => 'web']
);

// Assign admin role if not already assigned
if (!$user->hasRole('administrator')) {
    $user->assignRole('administrator');
    echo "✅ Admin role assigned to {$user->email}\n";
} else {
    echo "✅ User {$user->email} already has admin role\n";
}

echo "✅ Password: {$password}\n";
echo "✅ Admin user ready: {$user->email}\n";
