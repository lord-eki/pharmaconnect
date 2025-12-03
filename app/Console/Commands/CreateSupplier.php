<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;

class CreateSupplier extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'make:create-supplier';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create a new supplier user';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $email = 'supplier@pharmaconnect.com';
        $password = 'password';

        // Check if user already exists
        if (User::where('email', $email)->exists()) {
            $this->error('Supplier user already exists with email: ' . $email);
            return 1;
        }

        $user = User::create([
            'name' => 'Supplier User',
            'email' => $email,
            'password' => Hash::make($password),
            'email_verified_at' => now(),
        ]);

        $user->assignRole('Supplier');
        
        $this->info('Supplier user created successfully!');
        $this->info('Email: ' . $email);
        $this->info('Password: ' . $password);
        
        return 0;
    }
}