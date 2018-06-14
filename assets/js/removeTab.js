$(document).on('click', '[data-toggle="delete-tab"]', function(e) {
    e.stopPropagation();
    e.preventDefault && e.preventDefault();

    var $selfTab     = $(this).closest('li.nav-item');
    var $selfContent = $(this.getAttribute('data-target'));

    var $tablist     = $(this).closest('[role="tablist"]');
    var $tabContent  = $selfContent.closest('.tab-content');

    $selfTab.remove();
    $selfContent.remove();

    // Another tab already marked as active
    if ($tablist.find('li.active').length) {
        return true;
    }

    var $children = $tablist.find('a[data-toggle="tab"]');

    if ($children.length < 1) {
        $tabContent.find('div[data-role="empty-tab"]').addClass('show active');

        return true;
    }

    $children.first().click();
});
