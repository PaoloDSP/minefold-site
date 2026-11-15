<?php

namespace App\Filament\Resources\ProgramSlots;

use App\Filament\Resources\ProgramSlots\Pages\CreateProgramSlot;
use App\Filament\Resources\ProgramSlots\Pages\EditProgramSlot;
use App\Filament\Resources\ProgramSlots\Pages\ListProgramSlots;
use App\Filament\Resources\ProgramSlots\Schemas\ProgramSlotForm;
use App\Filament\Resources\ProgramSlots\Tables\ProgramSlotsTable;
use App\Models\ProgramSlot;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class ProgramSlotResource extends Resource
{
    protected static ?string $model = ProgramSlot::class;

    protected static ?string $modelLabel = 'créneau';

    protected static ?string $pluralModelLabel = 'grille des programmes';

    protected static ?int $navigationSort = 2;

    protected static ?string $navigationLabel = 'Grille des programmes';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedTv;

    public static function form(Schema $schema): Schema
    {
        return ProgramSlotForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return ProgramSlotsTable::configure($table);
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
            'index' => ListProgramSlots::route('/'),
            'create' => CreateProgramSlot::route('/create'),
            'edit' => EditProgramSlot::route('/{record}/edit'),
        ];
    }
}
