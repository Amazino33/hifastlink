<x-filament-panels::page>
    <div>
        @livewire('router-filter')
        @livewire('admin-stats')
    </div>

    <style>
        .no-scrollbar::-webkit-scrollbar { display: none; }
        .no-scrollbar { -ms-overflow-style: none; scrollbar-width: none; }
    </style>
</x-filament-panels::page>