document.addEventListener('DOMContentLoaded', function() {
    // Initialize Bootstrap tooltips
    var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
    var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl);
    });

    // Auto-dismiss alerts after 5 seconds
    var alerts = document.querySelectorAll('.alert');
    alerts.forEach(function(alert) {
        setTimeout(function() {
            alert.style.transition = 'opacity 0.5s ease-out';
            alert.style.opacity = '0';
            setTimeout(function() {
                alert.remove();
            }, 500);
        }, 5000);
    });

    // Confirm before performing destructive actions
    var deleteButtons = document.querySelectorAll('.btn-delete, .btn-danger');
    deleteButtons.forEach(function(button) {
        button.addEventListener('click', function(e) {
            if(!confirm('Are you sure you want to perform this action?')) {
                e.preventDefault();
            }
        });
    });
});