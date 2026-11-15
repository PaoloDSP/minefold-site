<?php

namespace App\Filament\Resources\Replays;

use App\Filament\Resources\Replays\Pages\CreateReplay;
use App\Filament\Resources\Replays\Pages\EditReplay;
use App\Filament\Resources\Replays\Pages\ListReplays;
use App\Filament\Resources\Replays\Schemas\ReplayForm;
use App\Filament\Resources\Replays\Tables\ReplaysTable;
use App\Models\Replay;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class ReplayResource extends Resource
{
    protected static ?string $model = Replay::class;

    protected static ?string $modelLabel = 'replay';

    protected static ?string $pluralModelLabel = 'replays';

    protected static ?int $navigationSort = 3;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedFilm;

    public static function form(Schema $schema): Schema
    {
        return ReplayForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return ReplaysTable::configure($table);
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
            'index' => ListReplays::route('/'),
            'create' => CreateReplay::route('/create'),
            'edit' => EditReplay::route('/{record}/edit'),
        ];
    }
}
