$(document).on('click', '[data-toggle="add-tab"]', function(e) {
    e.stopPropagation();
    e.preventDefault && e.preventDefault();

    var $tablist             = $(this.getAttribute('data-tab-list'));
    var $tabContentContainer = $(this.getAttribute('data-tab-content'));

    $.ajax({
        url: this.getAttribute('href'),
        cache: false
    }).done(function(response) {
        response = Array.isArray(response) ? response : [response];

        for (var i = 0; i < response.length; i++) {
            var arr = parseResponse($tablist, $tabContentContainer, $(response[i]));

            var $tabLink = arr.tablink;
            var $content = arr.content;

            if (i === 0) {
                $tabLink.find('a.nav-link').click();
            }
        }

        DASHTAINER.eventDataToggleTab($tablist);
        DASHTAINER.eventDataToggleTab($tabContentContainer);
    });
});

function parseResponse($tablist, $panelBodyContainer, row) {
    var $tabLink = $(row[0]['data']['tab']);
    var $content = $(row[0]['data']['content']);

    $tablist.append($tabLink);
    $panelBodyContainer.append($content);

    if ($content.find('[data-code-highlight]').length > 0) {
        DASHTAINER.runMisbehave($content);
    }

    return {
        tablink: $tabLink,
        content: $content
    };
}
