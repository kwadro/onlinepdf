document.addEventListener('DOMContentLoaded', function () {
    flatpickr('input[type="date"]', {
        altInput: true,
        altFormat: "Y-m-d",
        dateFormat: "Y-m-d"
    });
});
