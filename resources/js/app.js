import './bootstrap';

// Alpine is owned by Livewire — importing it separately here would create a second instance
// that lacks Livewire's registered plugins (e.g. Alpine.transaction), breaking Livewire actions.
// To add Alpine plugins, use: document.addEventListener('alpine:init', () => { ... })
// placed AFTER @livewireScripts in the layout.
