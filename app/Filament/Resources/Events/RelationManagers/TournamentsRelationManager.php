<?php

namespace App\Filament\Resources\Events\RelationManagers;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\TextInput;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class TournamentsRelationManager extends RelationManager
{
    protected static string $relationship = 'tournaments';

    protected static ?string $title = 'Tournois';

    protected static ?string $modelLabel = 'tournoi';

    public function form(Schema $schema): Schema
    {
        return $schema->columns(2)->components([
            TextInput::make('game')->label('Jeu')->required(),
            TextInput::make('format')->label('Format')->placeholder('Tournoi par équipes · 5v5 · PC'),
            TextInput::make('capacity_value')->label('Capacité (chiffre)')->placeholder('20'),
            TextInput::make('capacity_label')->label('Capacité (texte)')->placeholder('joueurs · 4 équipes'),
            TextInput::make('rules_url')->label('Lien du règlement')->url(),
            TextInput::make('cta_label')->label('Texte du bouton d’inscription')->placeholder('Inscrire mon équipe'),
            Repeater::make('phases')->label('Déroulé')->schema([
                TextInput::make('label')->label('Jour')->placeholder('SAMEDI')->required(),
                TextInput::make('title')->label('Titre')->required(),
                TextInput::make('text')->label('Détail'),
            ])->columns(3)->columnSpanFull()->defaultItems(0)->reorderable(),
            TextInput::make('sort')->label('Ordre')->numeric()->default(0),
        ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('game')
            ->reorderable('sort')
            ->defaultSort('sort')
            ->columns([
                TextColumn::make('game')->label('Jeu'),
                TextColumn::make('format')->label('Format'),
                TextColumn::make('capacity_value')->label('Capacité'),
            ])
            ->headerActions([CreateAction::make()])
            ->recordActions([EditAction::make(), DeleteAction::make()])
            ->toolbarActions([BulkActionGroup::make([DeleteBulkAction::make()])]);
    }
}
