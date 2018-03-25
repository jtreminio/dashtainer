$(document).on('click', '[data-toggle="card-remove"]', function(e) {
    e.stopPropagation();
    e.preventDefault && e.preventDefault();

    var $card = $(this).closest('div.card-container');
    $card.remove();

    return false;
});
