module.exports = function($container) {
    $('a[data-toggle="tab"]').on('shown.bs.tab', function (e) {
        $(this).closest('[role="tablist"]').find('li.nav-item').removeClass('active');
        $(this).closest('li.nav-item').addClass('active');
    });

    $('[data-update-text]').each(function(e) {
        DASHTAINER.updateTextTo($(this));
    });

    $container.find('[data-toggle="tooltip"]').tooltip({boundary: 'viewport'});
    DASHTAINER.dataMask($container);
};
