<?php

namespace App\Filament\Resources\Netdatas\Widgets;

use App\Models\Netdata;
use Filament\Actions\Action;
use Filament\Actions\Concerns\InteractsWithActions;
use Filament\Actions\Contracts\HasActions;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Infolists\Components\RepeatableEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Concerns\InteractsWithInfolists;
use Filament\Infolists\Contracts\HasInfolists;
use Filament\Schemas\Components\Group;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Concerns\InteractsWithSchemas;
use Filament\Schemas\Schema;
use Filament\Support\Colors\Color;
use Filament\Widgets\Widget;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\HtmlString;
use Illuminate\Support\Number;

class NetdataDisks extends Widget implements HasActions, HasForms, HasInfolists
{
    use InteractsWithActions;
    use InteractsWithForms;
    use InteractsWithInfolists;
    use InteractsWithSchemas {
        InteractsWithSchemas::getCachedSchemas as getCachedSchemasFromSchemas;
        InteractsWithForms::getCachedSchemas insteadof InteractsWithSchemas;
    }

    protected string $view = 'filament.resources.netdatas.widgets.netdata-disks';

    protected int|string|array $columnSpan = 1;

    public ?Netdata $record = null;

    protected $listeners = ['refresh-netdata-disks' => 'fetchData'];

    public array $disks = [];

    public array $allDisks = [];

    public function mount(): void
    {
        $this->fetchData();
    }

    public function fetchData(): void
    {
        $record = $this->record;
        if (! $record) {
            return;
        }

        $hostname = $record->ingressRule?->hostname;
        $path = $record->ingressRule?->path ?? '';
        $url = "https://{$hostname}{$path}";

        try {
            // Updated URL parameters as requested
            $apiUrl = "{$url}/api/v1/allmetrics?format=json&filter=disk_space.*&help=no&types=no&timings=no";

            $response = Http::withHeaders([
                'cf-access-client-id' => $record->access?->client_id,
                'cf-access-client-secret' => $record->access?->client_secret,
            ])->timeout(5)->get($apiUrl);

            if ($response->successful()) {
                $data = $response->json();
                $processedDisks = [];

                foreach ($data as $key => $diskData) {
                    // Extract dimensions
                    $dims = $diskData['dimensions'] ?? [];
                    $avail = $dims['avail']['value'] ?? 0;
                    $used = $dims['used']['value'] ?? 0;
                    $reserved = $dims['reserved_for_root']['value'] ?? 0;

                    // Netdata units are usually GB? The JSON says "units": "GiB".
                    // Values are floats.
                    $unit = $diskData['units'] ?? 'GiB'; // Should be GiB based on sample

                    $total = $avail + $used + $reserved;

                    // Format strings
                    // We can use Number::fileSize but we need to convert GiB to Bytes first?
                    // 1 GiB = 1024^3 bytes.
                    $multiplier = 1024 * 1024 * 1024;

                    $processedDisks[] = [
                        'name' => $diskData['family'] ?? $key, // '/mnt/data' etc
                        'used' => Number::fileSize($used * $multiplier, 2),
                        'avail' => Number::fileSize($avail * $multiplier, 2),
                        'total' => Number::fileSize($total * $multiplier, 2),
                        'percent' => $total > 0 ? round(($used / $total) * 100, 1) : 0,
                    ];
                }

                // Store all disks for the selection modal
                $this->allDisks = $processedDisks;

                // Filter disks based on saved settings
                $savedSettings = $record->disk_settings ?? [];

                if (! empty($savedSettings)) {
                    // Only show disks that are in the saved settings
                    $processedDisks = array_filter($processedDisks, function ($disk) use ($savedSettings) {
                        return in_array($disk['name'], $savedSettings);
                    });
                }

                $this->disks = array_values($processedDisks); // Re-index array

                // Update status to online since request was successful
                $record->update(['status' => 'online']);
            } else {
                // Update status to offline if response wasn't successful
                $record->update(['status' => 'offline']);
                $this->disks = [];
                $this->allDisks = [];
            }
        } catch (\Exception $e) {
            // Handle error and update status to offline
            $record->update(['status' => 'offline']);
            $this->disks = [];
            $this->allDisks = [];
        }
    }

    public function infolist(Schema $schema): Schema
    {
        return $schema
            ->schema([
                Section::make('Disks')
                    ->icon('mdi-harddisk')
                    ->iconColor(Color::Indigo)
                    ->headerActions([
                        $this->refreshAction(),
                    ])
                    ->schema([
                        RepeatableEntry::make('disks')
                            ->hiddenLabel()
                            ->state($this->disks)
                            ->schema(fn (array $state) => [
                                Group::make(function (Group $component) use ($state) {
                                    // State is already the array of disks? No, state is the whole array?
                                    // Filament RepeatableEntry iterates.
                                    // But unlike Form Repeater, Infolist RepeatableEntry takes a state array.
                                    // The schema callback receives the item state if using a closure?
                                    // Actually: ->schema(fn (array $state) => [ components based on item state ])?
                                    // Let's check NetdataSystemInfo implementation.
                                    // ->schema(fn (array $state) => [ Group::make(...) using $state[$index] ])

                                    $index = str($component->getStatePath())->remove('disks.')->toInteger();
                                    $disk = $state[$index] ?? [];

                                    return [
                                        TextEntry::make('usage')
                                            ->hiddenLabel()
                                            ->state($disk['name'] ?? 'Unknown')
                                            ->icon('mdi-harddisk')
                                            ->iconColor(Color::Indigo)
                                            ->afterContent(new HtmlString("<span class='text-nowrap whitespace-nowrap text-gray-400'>Free: {$disk['avail']} </span>"))
                                            ->belowContent(function () use ($disk) {
                                                $percent = $disk['percent'] ?? 0;

                                                $stat = ($disk['used'] ?? '0').' / '.($disk['total'] ?? '0');

                                                $colorClass = match (true) {
                                                    $percent >= 90 => 'bg-red-500',
                                                    $percent >= 70 => 'bg-yellow-500',
                                                    default => 'bg-indigo-500',
                                                };

                                                return TextEntry::make('progress_bar')
                                                    ->hiddenLabel()
                                                    ->state(new HtmlString("
                                                        <div class='w-full bg-gray-200 rounded-full h-2 dark:bg-gray-700 my-2'>
                                                            <div class='{$colorClass} h-2 min-w-1 rounded-full' style='width: {$percent}%'></div>
                                                        </div>
                                                        <span class='fi-sc-text'>{$stat}</span>
                                                    "));
                                            }),

                                    ];
                                })->extraAttributes([
                                    'class' => 'disk_space_widget_item',
                                ]),
                            ]),
                    ]),
            ]);
    }

    protected function refreshAction(): Action
    {
        return Action::make('refresh')
            ->outlined()
            ->hiddenLabel()
            ->icon('mdi-refresh')
            ->color('gray')
            ->button()
            ->tooltip('Click to refresh')
            ->action(function () {
                $this->fetchData();
                \Filament\Notifications\Notification::make()
                    ->title('Disks refreshed')
                    ->success()
                    ->send();
            });
    }
}
