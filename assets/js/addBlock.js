$(document).on('click', '[data-toggle="add-block"]', function(e) {
    e.stopPropagation();
    e.preventDefault && e.preventDefault();

    var $tablist             = $(this).closest('[role="tablist"]');
    var $tabContentContainer = $(this.getAttribute('aria-controls'));

    $.ajax({
        url: this.getAttribute('href'),
        cache: false
    }).done(function(response) {
        response = Array.isArray(response) ? response : [response];

        var tabLinkClicked = false;

        for (var i = 0; i < response.length; i++) {
            var $tabLink = parseResponse($tablist, $tabContentContainer, $(response[i]));

            if (!tabLinkClicked) {
                tabLinkClicked = true;
                $tabLink.find('a.nav-link').click();
            }
        }

        DASHTAINER.eventDataToggleTab();
    });
});

$(document).on('click', '[data-toggle="add-block-inline"]', function(e) {
    e.stopPropagation();
    e.preventDefault && e.preventDefault();

    var $inlineTarget = $(this.getAttribute('data-inline-controls'));

    $.ajax({
        url: this.getAttribute('href'),
        cache: false
    }).done(function(response) {
        response = Array.isArray(response) ? response : [response];

        for (var i = 0; i < response.length; i++) {
            parseResponse(undefined, $inlineTarget, $(response[i]));
        }

        DASHTAINER.eventDataToggleTab();
    });
});

function parseResponse($tablist, $panelBodyContainer, row) {
    var content = row[0]['data']['content'];

    $panelBodyContainer.append(content);

    var $content = $('[id="' + $(content)[0].id + '"]');
    DASHTAINER.dataMask($content);

    if ($content.find('[data-code-highlight]').length > 0) {
        DASHTAINER.runMisbehave($content);
    }

    if (row[0]['data']['tab'] !== undefined) {
        var $tabLink = $(row[0]['data']['tab']);
        $tablist.append($tabLink);
    }

    return $tabLink;
}
