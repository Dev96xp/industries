import Sort from '@alpinejs/sort';
import './project-calendar.js';
import './phone-mask.js';

document.addEventListener('alpine:init', () => {
    window.Alpine.plugin(Sort);
});
