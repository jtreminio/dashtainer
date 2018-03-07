module.exports = function() {
    $('a[data-toggle="tab"]').on('shown.bs.tab', function (e) {
        $(this).closest('[role="tablist"]').find('li.nav-item').removeClass('active');
        $(this).closest('li.nav-item').addClass('active');
    });
};
