<div class="fi-netdata-network-chart">
    <x-filament-apex-charts::chart :chart-id="$this->getChartId()" :chart-options="$this->getOptions()" :content-height="$this->getContentHeight()" :polling-interval="$this->getPollingInterval()"
        :loading-indicator="$this->getLoadingIndicator()" :dark-mode="$this->getDarkMode()" :ready-to-load="$this->readyToLoad" />
</div>
