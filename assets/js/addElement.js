$(document).on('click', '[data-toggle="add-element"]', function(e) {
    e.stopPropagation();
    e.preventDefault && e.preventDefault();

    var $target = $(this.getAttribute('data-target'));

    $.ajax({
        url: this.getAttribute('href'),
        cache: false
    }).done(function(response) {
        response = Array.isArray(response) ? response : [response];

        for (var i = 0; i < response.length; i++) {
            var $content = parseResponse($target, $(response[i]));
        }

        $content.find('[data-toggle="tooltip"]').tooltip({boundary: 'viewport'});
        DASHTAINER.dataMask($content);

        var $tableScroll      = $content.closest('table.table-scroll');
        var $scrollableParent = $content.closest('.scrollable-container');

        if ($tableScroll.length !== undefined) {
            $tableScroll.floatThead('reflow');
        }

        if ($scrollableParent.length !== undefined) {
            $scrollableParent.prop({ scrollTop: $scrollableParent.prop('scrollHeight') });
        }
    });
});

function parseResponse($target, row) {
    var $content = $(row[0]['data']);

    $target.append($content);

    return $content;
}
