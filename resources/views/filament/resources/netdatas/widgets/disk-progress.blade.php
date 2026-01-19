@php
    use Filament\Support\Enums\IconPosition;
    use Filament\Widgets\View\Components\StatsOverviewWidgetComponent\StatComponent\DescriptionComponent;
    use Illuminate\View\ComponentAttributeBag;

    // Mapping colors to Tailwind classes
    $colorClass = match ($progressColor ?? 'primary') {
        'danger' => 'bg-danger-50',
        'warning' => 'bg-warning-50',
        'success' => 'bg-success-50',
        'primary' => 'bg-primary-50',
        default => 'bg-gray-50',
    };

    $darkColorClass = match ($progressColor ?? 'primary') {
        'danger' => 'dark:bg-danger-900/20',
        'warning' => 'dark:bg-warning-900/20',
        'success' => 'dark:bg-success-900/20',
        'primary' => 'dark:bg-primary-900/20',
        default => 'dark:bg-gray-900/20',
    };

    $barColorClass = match ($progressColor ?? 'primary') {
        'danger' => 'bg-danger-100 border-danger-500 dark:bg-danger-500/20 dark:border-danger-400',
        'warning' => 'bg-warning-100 border-warning-500 dark:bg-warning-500/20 dark:border-warning-400',
        'success' => 'bg-success-100 border-success-500 dark:bg-success-500/20 dark:border-success-400',
        'primary' => 'bg-primary-100 border-primary-500 dark:bg-primary-500/20 dark:border-primary-400',
        default => 'bg-gray-100 border-gray-500 dark:bg-gray-500/20 dark:border-gray-400',
    };

    $descriptionColor = $getDescriptionColor() ?? 'gray';
    $descriptionIcon = $getDescriptionIcon();
    $descriptionIconPosition = $getDescriptionIconPosition();
    $url = $getUrl();
    $tag = $url ? 'a' : 'div';

    $percent = $percent ?? 0;

    $descriptionAttributes = new ComponentAttributeBag();
    $descriptionAttributes = $descriptionAttributes
        ->color(DescriptionComponent::class, $descriptionColor)
        ->class(['fi-wi-stats-overview-stat-description']);

    $descriptionIconAttributes = new \Illuminate\View\ComponentAttributeBag();
@endphp

<{!! $tag !!}
    @if ($url) {{ \Filament\Support\generate_href_html($url, $shouldOpenUrlInNewTab()) }} @endif
    {{ $getExtraAttributeBag()->class(['fi-wi-stats-overview-stat relative overflow-hidden']) }}>
    <!-- Background Progress Bar -->
    <div class="absolute inset-y-0 left-0 h-full {{ $barColorClass }} border-b-2 transition-all duration-500 ease-in-out"
        style="width: {{ $percent }}%"></div>

    <div class="fi-wi-stats-overview-stat-content relative z-10">
        <div class="fi-wi-stats-overview-stat-label-ctn">
            {{ \Filament\Support\generate_icon_html($getIcon()) }}

            <span class="fi-wi-stats-overview-stat-label">
                {{ $getLabel() }}
            </span>
        </div>

        <div class="fi-wi-stats-overview-stat-value">
            {{ $getValue() }}
        </div>

        @if ($description = $getDescription())
            <div {{ $descriptionAttributes }}>
                @if ($descriptionIcon && in_array($descriptionIconPosition, [IconPosition::Before, 'before']))
                    {{ \Filament\Support\generate_icon_html($descriptionIcon, attributes: $descriptionIconAttributes) }}
                @endif

                <span>
                    {{ $description }}
                </span>

                @if ($descriptionIcon && in_array($descriptionIconPosition, [IconPosition::After, 'after']))
                    {{ \Filament\Support\generate_icon_html($descriptionIcon, attributes: $descriptionIconAttributes) }}
                @endif
            </div>
        @endif
    </div>
    </{!! $tag !!}>
