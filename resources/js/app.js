import './bootstrap';

import Alpine from 'alpinejs';

window.Alpine = Alpine;

// Alpine.start() is intentionally not called here because Alpine is loaded via CDN in the main layout
// and Filament's Alpine components must initialize when the CDN script runs.
// If you prefer, you can re-enable `Alpine.start()` and remove the CDN include in the layout.
