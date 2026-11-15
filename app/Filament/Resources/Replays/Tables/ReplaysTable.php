<?php

namespace App\Filament\Resources\Replays\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class ReplaysTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->reorderable('sort')
            ->defaultSort('sort')
            ->columns([
                ImageColumn::make('thumbnail')->label('')->disk('public'),
                TextColumn::make('title')->label('Titre')->searchable(),
                TextColumn::make('kind')->label('Catégorie'),
                TextColumn::make('published_on')->label('Date')->date('d/m/Y'),
                IconColumn::make('is_visible')->label('Visible')->boolean(),
            ])
            ->recordActions([EditAction::make()])
            ->toolbarActions([BulkActionGroup::make([DeleteBulkAction::make()])]);
    }
}
