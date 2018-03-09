$(document).on('submit click', '[data-code-from-remote]', function(e) {
    e.stopPropagation();
    e.preventDefault && e.preventDefault();

    var $form = $(this).closest('form');
    var $contentContainer = $($(this).data('code-from-remote'));

    $.post(this.getAttribute('href'), $form.serialize())
        .done(function(response) {
            $contentContainer.text(response['data']);
            Prism.highlightElement($contentContainer[0]);
        });
});
