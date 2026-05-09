jQuery(function($) {
    // Theme toggle functionality
    function initTheme() {
        const savedTheme = localStorage.getItem('theme') || 'light';
        const themeToggle = $('#theme-toggle');
        const icon = themeToggle.find('i');

        if (savedTheme === 'dark') {
            document.documentElement.setAttribute('data-theme', 'dark');
            icon.removeClass('fa-moon').addClass('fa-sun');
        } else {
            document.documentElement.removeAttribute('data-theme');
            icon.removeClass('fa-sun').addClass('fa-moon');
        }
    }

    $('#theme-toggle').on('click', function() {
        const html = document.documentElement;
        const icon = $(this).find('i');
        const currentTheme = html.getAttribute('data-theme');

        if (currentTheme === 'dark') {
            html.removeAttribute('data-theme');
            localStorage.setItem('theme', 'light');
            icon.removeClass('fa-sun').addClass('fa-moon');
        } else {
            html.setAttribute('data-theme', 'dark');
            localStorage.setItem('theme', 'dark');
            icon.removeClass('fa-moon').addClass('fa-sun');
        }
    });

    // Initialize theme on page load
    initTheme();

    var navbar = $('.navbar');
    $(window).on('scroll', function() {
        if ($(window).scrollTop() > 20) {
            navbar.addClass('shadow-sm');
        } else {
            navbar.removeClass('shadow-sm');
        }
    });

    $('.edtech-faq-toggle').on('click', function() {
        $(this).next('.collapse').collapse('toggle');
    });
});