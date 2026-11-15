<?php

namespace App\Filament\Resources\ProgramSlots\Schemas;

use App\Models\ProgramSlot;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;

class ProgramSlotForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->columns(2)->components([
            Select::make('weekday')->label('Jour')->options(ProgramSlot::DAYS)->required(),
            TextInput::make('starts_at')->label('Heure')->placeholder('21:00 ou « Toute la journée »'),
            TextInput::make('title')->label('Émission')->required()->columnSpanFull(),
            Select::make('category')->label('Type')->options(ProgramSlot::CATEGORIES)->default('live')->required(),
            TextInput::make('sort')->label('Ordre')->numeric()->default(0),
            Toggle::make('is_active')->label('Active')->default(true),
        ]);
    }
}
