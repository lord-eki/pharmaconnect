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
    protected $description = 'Create a new Internal Operations user';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $name = $this->ask('Name');
        $email = $this->ask('Email');
        $password = $this->secret('Password');

        $validator = Validator::make([
            'name' => $name,
            'email' => $email,
            'password' => $password,
        ], [
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users,email',
            'password' => 'required|string|min:8',  
        ]);

        if($validator->fails()) {
            $this->error('User creation failed');
            foreach($validator->errors()->all() as $error) {
                $this->error($error);
            }
            return 1;
        }

         $user = User::create([
            'name' =>$name,
            'email' => $email,
            'password' => Hash::make($password),
    ]);

    $user->assignRole('Operation');
    $this->info('Operation user created successfully.');
    }

   
}
