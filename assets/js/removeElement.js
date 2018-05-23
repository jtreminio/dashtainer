$(document).on('click', '[data-toggle="delete-element"]', function(e) {
    e.stopPropagation();
    e.preventDefault && e.preventDefault();

    $(this.getAttribute('data-target')).remove();
});
