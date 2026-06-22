<?php

namespace App\Filament\Resources\Users\Pages;

use App\Filament\Resources\Users\UserResource;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Arr;

class CreateUser extends CreateRecord
{
    protected static string $resource = UserResource::class;

    protected static bool $canCreateAnother = false;

    public function handleRecordCreation(array $data): Model
    {
        $role = $data['role'];
        $new_data = Arr::except($data, 'role');
        $user = parent::handleRecordCreation($new_data);
        $user->assignRole($role);
        return $user;
    }

    public function getCreatedNotification():Notification
    {
        return Notification::make()
            ->title('User created successfully')
            ->success()
            ->icon('heroicon-o-check-circle')
            ->send();
    }
}
