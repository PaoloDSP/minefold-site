<?php

namespace App\Filament\Resources\Events\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class EventsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('starts_at', 'desc')
            ->columns([
                TextColumn::make('title')->label('Titre')->searchable(),
                TextColumn::make('starts_at')->label('Début')->dateTime('d/m/Y H:i')->sortable(),
                TextColumn::make('venue')->label('Lieu'),
                IconColumn::make('is_published')->label('Publié')->boolean(),
                IconColumn::make('is_featured')->label('À la une')->boolean(),
                TextColumn::make('helloasso_synced_at')->label('Synchro HelloAsso')->since(),
            ])
            ->recordActions([EditAction::make()])
            ->toolbarActions([BulkActionGroup::make([DeleteBulkAction::make()])]);
    }
}
