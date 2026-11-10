<?php

namespace App\Filament\Resources\Tours\RelationManagers;

use App\Filament\Resources\Transactions\TransactionResource;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;

class TransactionsRelationManager extends RelationManager
{
    protected static string $relationship = 'transactions';

    protected static ?string $title = 'Tur Gelir / Giderleri';

    protected static ?string $modelLabel = 'Hareket';

    public static function canViewForRecord(Model $ownerRecord, string $pageClass): bool
    {
        return (bool) auth()->user()?->isAdmin();
    }

    public function form(Schema $schema): Schema
    {
        return TransactionResource::form($schema);
    }

    public function table(Table $table): Table
    {
        return $table
            ->defaultSort('date', 'desc')
            ->columns(TransactionResource::tableColumns())
            ->headerActions([
                CreateAction::make()->label('Gider / Gelir Ekle')->fillForm(fn () => ['type' => 'expense', 'date' => now()]),
            ])
            ->recordActions([EditAction::make(), DeleteAction::make()]);
    }
}
