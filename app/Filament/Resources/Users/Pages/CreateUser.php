<?php

namespace App\Filament\Resources\Users\Pages;

use App\Filament\Resources\Users\UserResource;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;

class CreateUser extends CreateRecord
{
    protected static string $resource = UserResource::class;

    public function handleRecordCreation(array $data): Model
    {
        $user = parent::handleRecordCreation($data);
        $user->assignRole($data['role']);
        return $user;
    }
}
