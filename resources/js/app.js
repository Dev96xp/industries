import Sort from '@alpinejs/sort';
import './project-calendar.js';

document.addEventListener('alpine:init', () => {
    window.Alpine.plugin(Sort);
});
