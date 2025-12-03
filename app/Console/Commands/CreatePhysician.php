<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

class CreatePhysician extends Command
{
     /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'make:create-physician';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create a new physician user';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $email = 'phhysician@pharmaconnect.com';
        $password = 'password';

        // Check if user already exists
        if (User::where('email', $email)->exists()) {
            $this->error('Physician user already exists with email: ' . $email);
            return 1;
        }

        $user = User::create([
            'name' => 'Physician User',
            'email' => $email,
            'password' => Hash::make($password),
            'email_verified_at' => now(),
        ]);

        $user->assignRole('Physician');
        
        $this->info('Physician user created successfully!');
        $this->info('Email: ' . $email);
        $this->info('Password: ' . $password);
        
        return 0;
    }
}
