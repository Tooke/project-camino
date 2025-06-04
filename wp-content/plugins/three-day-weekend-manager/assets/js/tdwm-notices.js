document.addEventListener('DOMContentLoaded', function () {
    setTimeout(function () {
        document.querySelectorAll('.notice, .updated, .error').forEach(function (el) {
            el.style.transition = 'opacity 1s ease-out';
            el.style.opacity = '0';
            setTimeout(() => el.style.display = 'none', 1000);
        });
    }, 15000); // 15 seconds
});