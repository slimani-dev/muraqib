<?php

namespace App\Filament\Clusters\Settings\Pages;

use App\Filament\Clusters\Settings\SettingsCluster;
use App\Settings\WeatherSettings;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\Actions;
use Filament\Schemas\Components\Form;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Facades\Cache;

/**
 * The weather widget's location and background photos (WeatherSettings).
 *
 * @property-read Schema $form
 */
class Weather extends Page
{
    protected static ?string $cluster = SettingsCluster::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCloud;

    protected static ?int $navigationSort = 1;

    protected string $view = 'filament.clusters.settings.pages.weather';

    /**
     * @var array<string, mixed>
     */
    public array $data = [];

    public static function getNavigationLabel(): string
    {
        return 'Weather';
    }

    /**
     * A plain Page has no policy gate of its own; settings are for admins.
     */
    public static function canAccess(): bool
    {
        return (bool) auth()->user()?->is_admin;
    }

    public function mount(WeatherSettings $settings): void
    {
        $this->form->fill([
            'latitude' => $settings->latitude,
            'longitude' => $settings->longitude,
            'location_name' => $settings->location_name,
        ]);
    }

    public function form(Schema $schema): Schema
    {
        $keyStored = filled(app(WeatherSettings::class)->unsplash_key);

        return $schema
            ->components([
                Form::make([
                    Section::make('Location')
                        ->description('Forecasts come from Open-Meteo (no key needed). Leave the coordinates empty to hide the forecast.')
                        ->columns(3)
                        ->schema([
                            TextInput::make('latitude')
                                ->numeric()
                                ->minValue(-90)
                                ->maxValue(90)
                                ->requiredWith('longitude'),
                            TextInput::make('longitude')
                                ->numeric()
                                ->minValue(-180)
                                ->maxValue(180)
                                ->requiredWith('latitude'),
                            TextInput::make('location_name')
                                ->label('Name shown on the widget')
                                ->maxLength(100)
                                ->placeholder('e.g. Algiers'),
                        ]),
                    Section::make('Background photos')
                        ->description('Optional: an Unsplash photo matching the weather behind the widget.')
                        ->schema([
                            TextInput::make('unsplash_key')
                                ->label('Unsplash access key')
                                ->password()
                                ->revealable()
                                ->placeholder($keyStored ? 'Stored. Leave blank to keep it' : null)
                                ->helperText('Create one at unsplash.com/developers. Stored encrypted.'),
                        ]),
                ])
                    ->livewireSubmitHandler('save')
                    ->footer([
                        Actions::make([
                            Action::make('save')
                                ->submit('save')
                                ->keyBindings(['mod+s']),
                            Action::make('removeUnsplashKey')
                                ->label('Remove Unsplash key')
                                ->color('danger')
                                ->link()
                                ->visible($keyStored)
                                ->requiresConfirmation()
                                ->action(function (WeatherSettings $settings): void {
                                    $settings->unsplash_key = null;
                                    $settings->save();
                                    Cache::forget('weather_sba');

                                    Notification::make()->success()->title('Unsplash key removed')->send();
                                    $this->redirect(static::getUrl());
                                }),
                        ]),
                    ]),
            ])
            ->statePath('data');
    }

    public function save(WeatherSettings $settings): void
    {
        $state = $this->form->getState();

        $settings->latitude = filled($state['latitude'] ?? null) ? (float) $state['latitude'] : null;
        $settings->longitude = filled($state['longitude'] ?? null) ? (float) $state['longitude'] : null;
        $settings->location_name = $state['location_name'] ?: null;

        // A blank key field keeps the stored key
        if (filled($state['unsplash_key'] ?? null)) {
            $settings->unsplash_key = $state['unsplash_key'];
        }

        $settings->save();

        // Show the new location on the next dashboard load
        Cache::forget('weather_sba');

        $this->form->fill([...$state, 'unsplash_key' => null]);

        Notification::make()->success()->title('Saved')->send();
    }
}
