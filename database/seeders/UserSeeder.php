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
    {
        
I have a laravel + Filamentphp+ spatie laravel permission . I want to create an admin seeder and assign role Admin

I'll help you create an admin seeder with the Spatie Laravel Permission package in your Laravel + Filament application.

AdminSeeder.php
Code 
Now follow these steps to implement the seeder:

1. Create the Seeder File
Run the Artisan command to create the seeder (or just use the file I provided above):

bash
php artisan make:seeder AdminSeeder
Then replace the content with the code in the artifact above.

2. Update DatabaseSeeder
Open database/seeders/DatabaseSeeder.php and add the AdminSeeder:

php
public function run(): void
{
    $this->call([
        AdminSeeder::class,
    ]);
}
3. Run the Seeder
Execute the seeder with:

bash
php artisan db:seed --class=AdminSeeder
Or run all seeders:

bash
php artisan db:seed
4. Important Security Notes
Before deploying to production:

Change the default password in the seeder
Consider using environment variables for admin credentials
Use a strong, unique password
Optional: Environment-Based Credentials
You can make it more secure by using environment variables:

php
$admin = User::firstOrCreate(
    ['email' => env('ADMIN_EMAIL', 'admin@example.com')],
    [
        'name' => env('ADMIN_NAME', 'Admin User'),
        'password' => Hash::make(env('ADMIN_PASSWORD', 'password')),
        'email_verified_at' => now(),
    ]
);
Then add to your .env file:

ADMIN_EMAIL=admin@yourdomain.com
ADMIN_NAME=Admin User
ADMIN_PASSWORD=your_secure_password_here
The seeder will now create an admin user with the "Admin" role assigned. You can login to your Filament panel using the credentials specified in the seeder!






<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use Illuminate\Support\Facades\Hash;

class AdminSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {

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
