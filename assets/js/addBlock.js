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

function parseResponse($tablist, $panelBodyContainer, row) {
    var $tabLink = $(row[0]['data']['tab']);
    var content  = row[0]['data']['content'];

    $tablist.append($tabLink);
    $panelBodyContainer.append(content);

    return $tabLink;
}
