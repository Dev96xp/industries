import { Calendar } from '@fullcalendar/core';
import dayGridPlugin from '@fullcalendar/daygrid';
import listPlugin from '@fullcalendar/list';
import interactionPlugin from '@fullcalendar/interaction';

window.initProjectCalendar = function (rootEl, events, wire) {
    const el = rootEl.querySelector('#project-calendar');
    if (!el || el._fcInitialized) return;
    el._fcInitialized = true;

    const calendar = new Calendar(el, {
        plugins: [dayGridPlugin, listPlugin, interactionPlugin],
        initialView: 'dayGridMonth',
        headerToolbar: {
            left: 'prev,next today',
            center: 'title',
            right: 'dayGridMonth,listMonth',
        },
        buttonText: {
            today: 'Today',
            month: 'Month',
            list: 'List',
        },
        events: events,
        editable: true,
        eventResizableFromStart: true,
        eventDrop: function (info) {
            wire.updateProjectDates(
                info.event.id,
                info.event.startStr,
                info.event.endStr || null
            );
        },
        eventResize: function (info) {
            wire.updateProjectDates(
                info.event.id,
                info.event.startStr,
                info.event.endStr || null
            );
        },
        eventClick: function (info) {
            info.jsEvent.preventDefault();
            if (info.event.url) {
                window.location.href = info.event.url;
            }
        },
        eventDidMount: function (info) {
            const client = info.event.extendedProps.client;
            if (client) {
                info.el.title = info.event.title + '\nClient: ' + client;
            }
        },
        aspectRatio: 1.6,
        firstDay: 0,
    });

    calendar.render();
};
