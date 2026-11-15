<?php

namespace App\Filament\Resources\ProgramSlots\Tables;

use App\Models\ProgramSlot;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class ProgramSlotsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('weekday')
            ->columns([
                TextColumn::make('weekday')->label('Jour')->formatStateUsing(fn ($state) => ProgramSlot::DAYS[$state] ?? $state)->sortable(),
                TextColumn::make('starts_at')->label('Heure'),
                TextColumn::make('title')->label('Émission')->searchable(),
                TextColumn::make('category')->label('Type')->badge()->formatStateUsing(fn ($state) => ProgramSlot::CATEGORIES[$state] ?? $state),
                IconColumn::make('is_active')->label('Active')->boolean(),
            ])
            ->filters([SelectFilter::make('weekday')->label('Jour')->options(ProgramSlot::DAYS)])
            ->recordActions([EditAction::make()])
            ->toolbarActions([BulkActionGroup::make([DeleteBulkAction::make()])]);
    }
}
