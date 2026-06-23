<?php

namespace App\Filament\Insurer\Resources\Insurance\ClaimVerifications;

use App\Filament\Insurer\Resources\Insurance\ClaimVerifications\Pages\CreateClaimVerification;
use App\Filament\Insurer\Resources\Insurance\ClaimVerifications\Pages\EditClaimVerification;
use App\Filament\Insurer\Resources\Insurance\ClaimVerifications\Pages\ListClaimVerifications;
use App\Filament\Insurer\Resources\Insurance\ClaimVerifications\Pages\ViewClaimVerification;
use App\Filament\Insurer\Resources\Insurance\ClaimVerifications\Schemas\ClaimVerificationForm;
use App\Filament\Insurer\Resources\Insurance\ClaimVerifications\Tables\ClaimVerificationsTable;
use App\Models\InsuranceClaim;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Filament\Notifications\Notification;
use UnitEnum;

class ClaimVerificationResource extends Resource
{
    protected static ?string $model = InsuranceClaim::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedShieldCheck;

    protected static ?string $navigationLabel = 'Claims';

    protected static string|UnitEnum|null $navigationGroup = 'Claim Management';

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->where('insurance_provider_id', auth()->user()->insuranceProvider->id ?? 0)
            ->with([
                'patient',
                'prescription.items.medicine',
                'prescription.orders',
                'externalOrder',
            ]);
    }

    public static function form(Schema $schema): Schema
    {
        return ClaimVerificationForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return ClaimVerificationsTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListClaimVerifications::route('/'),
            'create' => CreateClaimVerification::route('/create'),
            // 'edit' => EditClaimVerification::route('/{record}/edit'),
            // 'view' => ViewClaimVerification::route('/{record}'),
        ];
    }

    public static function getNavigationBadge(): ?string

    {


        if(auth()->user()->insuranceProvider == null)
            {
                Notification::make()->icon('heroicon-o-user-circle')->body('Your account is yet to be set up.')
                ->info()->send();

                return null;

            }else{
            $count = static::getModel()::where('status', 'submitted')
            ->where('insurance_provider_id', auth()->user()->insuranceProvider->id)
            ->count();

             return $count > 0 ? (string) $count : null;
            }
   
    }
}