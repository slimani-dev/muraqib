<div class="flex flex-col gap-6 w-full">
    @if($widgets)
        <x-filament-widgets::widgets
            :widgets="$widgets"
            :columns="[
                'default' => 1,
                'md' => 1,
                'lg' => 1,
                'xl' => 1,
            ]"
            :data="['record' => $record]"
            class="w-full"
        />
    @endif
</div>
