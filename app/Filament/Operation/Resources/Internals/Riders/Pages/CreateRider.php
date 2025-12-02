<?php

namespace App\Filament\Operation\Resources\Internals\Riders\Pages;

use App\Filament\Operation\Resources\Internals\Riders\RiderResource;
use App\Models\Rider;
use App\Models\User;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;

class CreateRider extends CreateRecord
{
    protected static string $resource = RiderResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        return DB::transaction((function () use ($data) {


            $user = User::create([
                'name' => $data['first_name'].' '.$data['last_name'],
                'email' => $data['email'],
                'phone' => $data['phone'],
                'password' => Hash::make('pharmaconnect_rider'),
            ]);

          

            $user->assignRole('Rider');

            $lastRider = Rider::latest('id')->first();
            $nextNumber = $lastRider
                ? ((int) substr($lastRider->rider_code, 4)) + 1
                : 1;

            $data['rider_code'] = 'RDR-'.str_pad($nextNumber, 3, '0', STR_PAD_LEFT);

            $data['user_id'] = $user->id;

            return $data;
        }));
    }
}
