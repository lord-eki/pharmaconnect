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
        DB::table('users')->insert([
            'email' => fake()->unique()->safeEmail(),
            'email_verified_at' => now(),
            'password' => Hash::make('password'),
            'phone' => fake()->unique()->phoneNumber(),
            'phone_verified_at' => now(),
            'first_name' => fake()->firstName(),
            'last_name' => fake()->lastName(),
            'profile_image' => fake()->imageUrl(),
            'is_active' => true,
            'last_login_at' => fake()->optional()->dateTimeBetween('-1 year', 'now'),
            'two_factor_secret' => null,
            'two_factor_recovery_codes' => null,
            'remember_token' => Str::random(10),
        ]);
    }
}
