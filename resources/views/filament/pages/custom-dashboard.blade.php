<x-filament-panels::page>
    @vite(['resources/css/filament-overrides.css'])

    <form wire:submit.prevent class="mb-4">
        {{ $this->filtersForm }}
    </form>

    <x-filament-widgets::widgets
        :columns="$this->getColumns()"
        :data="$this->getWidgetData()"
        :widgets="$this->getVisibleWidgets()"
    />
</x-filament-panels::page>
