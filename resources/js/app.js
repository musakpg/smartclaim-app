import './bootstrap';

// Global Flatpickr configuration & anchored static initialization helper
if (typeof flatpickr !== 'undefined') {
    flatpickr.setDefaults({
        dateFormat: "d/m/Y",
        altInput: true,
        altFormat: "d/m/Y",
        maxDate: "today",
        static: true, // Anchors the calendar popup directly below the input wrapper
        disableMobile: true, // Prevents OS picker interference if using styled Flatpickr
    });
}

window.applyFlatpickr = function(target, options = {}) {
    if (typeof flatpickr === 'undefined') return null;
    return flatpickr(target, Object.assign({
        dateFormat: "d/m/Y",
        altInput: true,
        altFormat: "d/m/Y",
        maxDate: "today",
        static: true,
        disableMobile: true,
    }, options));
};
