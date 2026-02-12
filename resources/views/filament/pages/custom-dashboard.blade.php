<x-filament-panels::page>
    {{-- Keep Filament's default styles intact. Use a tiny, safe overrides bundle for admin-only tweaks. --}}
    @vite(['resources/css/filament-overrides.css'])

    <div class="space-y-6">
        @livewire(\App\Filament\Widgets\RouterFilterWidget::class)
        @livewire(\App\Filament\Widgets\AdminStatsWidget::class)
    </div>
</x-filament-panels::page>