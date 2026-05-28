// Guest pages (login, register) need Alpine started locally.
// We cannot call Alpine.start() in app.js because Filament's admin pages load
// Alpine via CDN and register their own components before start() is called.
// This file is only included on guest layouts, keeping concerns separate.
import Alpine from 'alpinejs';
window.Alpine = Alpine;
Alpine.start();
