<?php

namespace App\Filament\Resources\Events\Schemas;

use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\TagsInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Schemas\Schema;
use Illuminate\Support\Str;

class EventForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->columns(1)
            ->components([
                Tabs::make('event')->tabs([
                    Tab::make('Général')->schema([
                        Grid::make(2)->schema([
                            TextInput::make('title')->label('Titre')->required()->maxLength(255)
                                ->live(onBlur: true)
                                ->afterStateUpdated(fn ($state, callable $set, $get) => $get('slug') ? null : $set('slug', Str::slug($state))),
                            TextInput::make('slug')->label('Adresse (slug)')->required()->unique(ignoreRecord: true)
                                ->helperText('Utilisé dans l’URL : /evenements/mon-slug'),
                            TextInput::make('tagline')->label('Accroche')->columnSpanFull(),
                            TagsInput::make('badges')->label('Badges')->placeholder('LAN PARTY, BYOC…')->columnSpanFull(),
                            DateTimePicker::make('starts_at')->label('Début')->required()->seconds(false),
                            DateTimePicker::make('ends_at')->label('Fin')->seconds(false),
                            TextInput::make('hours_label')->label('Horaires affichés')->placeholder('9 h – 18 h'),
                            TextInput::make('registration_label')->label('Ouverture des inscriptions')->placeholder('Ex. : 1er mars 2027'),
                            TextInput::make('venue')->label('Lieu'),
                            TextInput::make('address')->label('Adresse'),
                            TextInput::make('contact_email')->label('E-mail de contact')->email(),
                            TextInput::make('map_url')->label('Lien carte (Google Maps, OpenStreetMap…)')->url(),
                            Textarea::make('intro')->label('Présentation')->rows(4)->columnSpanFull(),
                            Toggle::make('is_published')->label('Publié'),
                            Toggle::make('is_featured')->label('Mis en avant sur l’accueil'),
                        ]),
                    ]),
                    Tab::make('Billetterie HelloAsso')->schema([
                        TextInput::make('helloasso_form_slug')->label('Slug du formulaire HelloAsso')
                            ->helperText('La fin de l’adresse HelloAsso, ex. « geek-area-party ». Sert à récupérer les places vendues.'),
                        TextInput::make('helloasso_form_type')->label('Type de formulaire')->default('Event')
                            ->helperText('Event pour une billetterie (valeur par défaut).'),
                        TextInput::make('checkout_url')->label('Lien de paiement (facultatif)')->url()
                            ->helperText('Laissez vide pour utiliser l’adresse HelloAsso construite à partir du slug.'),
                    ]),
                    Tab::make('Challenge')->schema([
                        TextInput::make('challenge_title')->label('Titre'),
                        Textarea::make('challenge_text')->label('Texte')->rows(3),
                        TextInput::make('challenge_games')->label('Jeux envisagés'),
                        Repeater::make('challenge_modes')->label('Types d’épreuves')->schema([
                            TextInput::make('title')->label('Titre')->required(),
                            TextInput::make('text')->label('Description'),
                        ])->columns(2)->defaultItems(0)->reorderable(),
                    ]),
                    Tab::make('Programme')->schema([
                        Repeater::make('program')->label('Jours')->schema([
                            TextInput::make('day')->label('Jour')->placeholder('Samedi 9 octobre')->required(),
                            TextInput::make('subtitle')->label('Sous-titre'),
                            Repeater::make('items')->label('Créneaux')->schema([
                                TextInput::make('time')->label('Heure')->placeholder('10:00'),
                                TextInput::make('label')->label('Activité')->required(),
                            ])->columns(2)->defaultItems(1)->reorderable(),
                        ])->defaultItems(0)->reorderable()->collapsible(),
                        Textarea::make('program_note')->label('Note sous le programme')->rows(2),
                    ]),
                    Tab::make('Matériel & récompenses')->schema([
                        Textarea::make('byoc_intro')->label('Intro matériel (BYOC)')->rows(2),
                        TagsInput::make('byoc_items')->label('Liste du matériel'),
                        Textarea::make('rewards_intro')->label('Intro récompenses')->rows(2),
                        TagsInput::make('rewards')->label('Récompenses'),
                    ]),
                ]),
            ]);
    }
}
