<?php

namespace App\Filament\Resources\Events\RelationManagers;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\TagsInput;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class TicketTypesRelationManager extends RelationManager
{
    protected static string $relationship = 'ticketTypes';

    protected static ?string $title = 'Billets';

    protected static ?string $modelLabel = 'billet';

    public function form(Schema $schema): Schema
    {
        return $schema->columns(2)->components([
            TextInput::make('name')->label('Nom')->required()->maxLength(255),
            TextInput::make('tag')->label('Étiquette')->placeholder('JOUEUR, TOURNOI, VISITEUR'),
            TextInput::make('price_cents')->label('Prix (en centimes)')->numeric()->minValue(0)
                ->helperText('1500 = 15 €. Mis à jour automatiquement si le tarif est lié à HelloAsso.'),
            TextInput::make('price_label')->label('Prix affiché (remplace le prix)')->placeholder('À confirmer'),
            TextInput::make('capacity')->label('Nombre de places')->numeric()->minValue(0),
            TextInput::make('capacity_label')->label('Texte de capacité')->placeholder('4 équipes'),
            TextInput::make('helloasso_tier_id')->label('ID du tarif HelloAsso')->numeric()
                ->helperText('Permet d’afficher les places restantes en direct.'),
            TextInput::make('checkout_url')->label('Lien de paiement spécifique')->url(),
            TagsInput::make('perks')->label('Ce qui est inclus')->columnSpanFull(),
            TextInput::make('sort')->label('Ordre')->numeric()->default(0),
            Toggle::make('is_visible')->label('Visible')->default(true),
        ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('name')
            ->reorderable('sort')
            ->defaultSort('sort')
            ->columns([
                TextColumn::make('name')->label('Nom'),
                TextColumn::make('tag')->label('Étiquette'),
                TextColumn::make('price')->label('Prix')->state(fn ($record) => $record->priceText()),
                TextColumn::make('entries_taken')->label('Vendus')->placeholder('—'),
                TextColumn::make('availability')->label('Disponibilité')->state(fn ($record) => $record->availabilityText())->placeholder('—'),
                IconColumn::make('is_visible')->label('Visible')->boolean(),
            ])
            ->headerActions([CreateAction::make()])
            ->recordActions([EditAction::make(), DeleteAction::make()])
            ->toolbarActions([BulkActionGroup::make([DeleteBulkAction::make()])]);
    }
}
