<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

class CreateOperation extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'make:create-operation';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create a new Operation user';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $email = 'operation@pharmaconnect.com';
        $password = 'password';

        // Check if user already exists
        if (User::where('email', $email)->exists()) {
            $this->error('Operation user already exists with email: ' . $email);
            return 1;
        }

        $user = User::create([
            'name' => 'Operation User',
            'email' => $email,
            'password' => Hash::make($password),
            'email_verified_at' => now(),
        ]);

        $user->assignRole('Operation');
        
        $this->info('Operation user created successfully!');
        $this->info('Email: ' . $email);
        $this->info('Password: ' . $password);
        
        return 0;
    }
   
}
