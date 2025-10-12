<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void

        // Create permissions and assign them to Admin role
        $permissions = [
            'view users',
            'create users',
            'edit users',
            'delete users',
        
        ];

        foreach ($permissions as $permission) {
            $perm = Permission::firstOrCreate(['name' => $permission]);
            $adminRole->givePermissionTo($perm);
        }

        // Create Admin User
        $admin = User::firstOrCreate(
            ['email' => 'admin@pharmaconnect.com'],
            [
                'name' => 'Admin User',
                'password' => Hash::make('password'), 
                'email_verified_at' => now(),
            ]
        );

        // Assign Admin role to the user
        $admin->assignRole('Admin');
    }
}
