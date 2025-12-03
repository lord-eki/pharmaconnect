<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

class CreateInsurer extends Command
{
     /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'make:create-insurer';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create a new insurance user';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $email = 'insurer@pharmaconnect.com';
        $password = 'password';

        // Check if user already exists
        if (User::where('email', $email)->exists()) {
            $this->error('Insurance user already exists with email: ' . $email);
            return 1;
        }

        $user = User::create([
            'name' => 'Insurer User',
            'email' => $email,
            'password' => Hash::make($password),
            'email_verified_at' => now(),
        ]);

        $user->assignRole('Insurer');
        
        $this->info('Insurer user created successfully!');
        $this->info('Email: ' . $email);
        $this->info('Password: ' . $password);
        
        return 0;
    }
   
}
