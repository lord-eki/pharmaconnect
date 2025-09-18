<?php

namespace App\Filament\Resources\OrderItems;

use App\Filament\Resources\OrderItems\Pages\ManageOrderItems;
use App\Models\OrderItem;
use BackedEnum;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use UnitEnum;

class OrderItemResource extends Resource
{
    protected static ?string $model = OrderItem::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCircleStack;

    protected static string|UnitEnum|null $navigationGroup = 'Order & Quotations';

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make()->schema([
                    Select::make('order_id')
                        ->relationship('order', 'id')
                        ->required(),
                    Select::make('quotation_item_id')
                        ->relationship('quotationItem', 'id')
                        ->required(),
                    Select::make('medicine_id')
                        ->relationship('medicine', 'generic_name')
                        ->required(),
                    TextInput::make('quantity')
                        ->required()
                        ->numeric(),
                    TextInput::make('unit_price')
                        ->required()
                        ->numeric(),
                    TextInput::make('total_price')
                        ->required()
                        ->numeric(),
                    Select::make('status')
                        ->options([
                            'pending' => 'Pending',
                            'confirmed' => 'Confirmed',
                            'shipped' => 'Shipped',
                            'delivered' => 'Delivered',
                        ])
                        ->default('pending')
                        ->required(),
                ])->columns(2)->columnSpanFull(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('created_at')
                    ->date()->label('Date')
                    ->sortable(),
                TextColumn::make('order.id')
                    ->searchable(),
                TextColumn::make('quotationItem.id')
                    ->searchable(),
                TextColumn::make('medicine.id')
                    ->searchable(),
                TextColumn::make('quantity')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('unit_price')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('total_price')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('status'),

            ])
            ->filters([
                //
            ])
            ->recordActions([
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ManageOrderItems::route('/'),
        ];
    }
}
