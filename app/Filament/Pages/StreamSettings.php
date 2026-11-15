<?php

namespace App\Filament\Pages;

use App\Models\Setting;
use App\Services\StreamStatus;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Forms\Components\Radio;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\Actions;
use Filament\Schemas\Components\EmbeddedSchema;
use Filament\Schemas\Components\Form;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;

/**
 * @property-read Schema $form
 */
class StreamSettings extends Page
{
    public const KEYS = [
        'twitch_channel', 'player_mode', 'fallback_hls_url', 'fallback_label',
        'live_now', 'now_title', 'next_title', 'next_time',
        'membership_url', 'donation_url', 'shop_url', 'discord_url',
    ];

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedSignal;

    protected static ?string $navigationLabel = 'Diffusion & liens';

    protected static ?string $title = 'Diffusion & liens';

    protected static ?int $navigationSort = 0;

    public ?array $data = [];

    public function mount(): void
    {
        $this->form->fill(
            collect(self::KEYS)->mapWithKeys(fn ($k) => [$k => Setting::get($k)])->all()
            + ['player_mode' => Setting::get('player_mode', 'auto'), 'live_now' => (bool) Setting::get('live_now')]
        );
    }

    public function form(Schema $schema): Schema
    {
        return $schema->statePath('data')->components([
            Section::make('Lecteur')->columns(2)->schema([
                Radio::make('player_mode')->label('Source du lecteur')->options([
                    'auto' => 'Automatique : Twitch quand la chaîne est en direct, sinon le flux Minefold (ffplayout)',
                    'twitch' => 'Forcer Twitch',
                    'fallback' => 'Forcer le flux Minefold (secours)',
                ])->default('auto')->columnSpanFull(),
                TextInput::make('twitch_channel')->label('Chaîne Twitch')->placeholder('minefold_webtv'),
                TextInput::make('fallback_hls_url')->label('Flux de secours (HLS .m3u8)')->url()
                    ->placeholder('https://tv.minefold.fr/live/stream.m3u8')
                    ->helperText('Sortie HLS de ffplayout, servie en HTTPS.'),
                TextInput::make('fallback_label')->label('Nom affiché du flux de secours')->placeholder('Minefold TV'),
            ]),
            Section::make('À l’antenne')->description('Texte affiché sous le lecteur, et étiquette direct / rediffusion.')->columns(3)->schema([
                Toggle::make('live_now')->label('Nous sommes en direct maintenant')
                    ->helperText('Affiche l’étiquette « EN DIRECT ». À décocher à la fin du direct. (Sera automatique une fois ffplayout relié.)')
                    ->columnSpanFull(),
                TextInput::make('now_title')->label('Émission en cours (hors direct)'),
                TextInput::make('next_title')->label('Émission suivante'),
                TextInput::make('next_time')->label('Heure de la suivante')->placeholder('21:00'),
            ]),
            Section::make('Liens de l’association')->columns(2)->schema([
                TextInput::make('membership_url')->label('Adhésion (HelloAsso)')->url(),
                TextInput::make('donation_url')->label('Faire un don (HelloAsso)')->url(),
                TextInput::make('shop_url')->label('Boutique')->url(),
                TextInput::make('discord_url')->label('Discord')->url(),
            ]),
        ]);
    }

    public function content(Schema $schema): Schema
    {
        return $schema->components([
            Form::make([EmbeddedSchema::make('form')])
                ->id('form')
                ->livewireSubmitHandler('save')
                ->footer([
                    Actions::make([
                        Action::make('save')->label('Enregistrer')->submit('save')->keyBindings(['mod+s']),
                    ]),
                ]),
        ]);
    }

    public function save(): void
    {
        $data = $this->form->getState();
        Setting::put(collect(self::KEYS)->mapWithKeys(fn ($k) => [$k => $data[$k] ?? null])->all());
        StreamStatus::forget();

        Notification::make()->title('Réglages enregistrés')->success()->send();
    }
}
