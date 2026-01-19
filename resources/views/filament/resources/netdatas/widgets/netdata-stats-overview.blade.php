<div class="flex flex-col gap-6">
    @livewire(\App\Filament\Resources\Netdatas\Widgets\NetdataCpuStats::class, ['record' => $record])
    @livewire(\App\Filament\Resources\Netdatas\Widgets\NetdataMemoryStats::class, ['record' => $record])
    @livewire(\App\Filament\Resources\Netdatas\Widgets\NetdataNetworkStats::class, ['record' => $record])
    @livewire(\App\Filament\Resources\Netdatas\Widgets\NetdataDisksStats::class, ['record' => $record])
</div>
