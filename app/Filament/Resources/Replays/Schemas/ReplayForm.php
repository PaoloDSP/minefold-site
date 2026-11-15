<?php

namespace App\Filament\Resources\Replays\Schemas;

use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;

class ReplayForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->columns(2)->components([
            TextInput::make('title')->label('Titre')->required(),
            TextInput::make('kind')->label('Catégorie')->placeholder('Souvenirs de LAN, Replay de live…'),
            TextInput::make('url')->label('Lien (vidéo, article, galerie)')->url()->columnSpanFull(),
            FileUpload::make('thumbnail')->label('Miniature')->image()->disk('public')->directory('replays')->imageEditor(),
            DatePicker::make('published_on')->label('Date'),
            TextInput::make('sort')->label('Ordre')->numeric()->default(0),
            Toggle::make('is_visible')->label('Visible')->default(true),
        ]);
    }
}
