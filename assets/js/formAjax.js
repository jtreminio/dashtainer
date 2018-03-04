$(document).on('submit', 'form[data-ajax]', function (e) {
    e.preventDefault();
    e.stopPropagation();

    getNonFormElementData($(this));

    var url      = $(this).attr('action');
    var $spinner = $('#ajax-spinner');

    $spinner.removeClass('hidden invisible');

    $.post(url, $(this).serialize(), function (response) {
        handleFormAjaxResponse(response);
    })
        .fail(function (data) {
            if (data['responseJSON'] === undefined) {
                location.reload();

                return true;
            }

            DASHTAINER.formErrors(data['responseJSON']['errors']);
            $spinner.addClass('hidden invisible');

            return true;
        });
});

function handleFormAjaxResponse(response) {
    var $spinner      = $('#ajax-spinner');
    var $dynamicModal = $('#dynamic-modal');
    var $allModals    = $('.modal');

    if (response['type'] === 'success') {
        $allModals.modal('hide');
        $spinner.addClass('hidden invisible');
        DASHTAINER.formErrors({});

        return true;
    }

    // Close any open modals
    if (response['type'] === 'modal_close') {
        $allModals.modal('hide');
        $spinner.addClass('hidden invisible');

        return true;
    }

    // Show an existing modal with contents
    if (response['type'] === 'modal') {
        $allModals.modal('hide');
        $(response.data).modal('show');
        $spinner.addClass('hidden invisible');

        return true;
    }

    if (response['type'] === 'modal_content') {
        $allModals.modal('hide');
        $spinner.addClass('hidden invisible');
        $dynamicModal.find('.modal-content').html(response.data);

        if (response.hasOwnProperty('modalSize')) {
            $dynamicModal.find('.modal-dialog').addClass(response['modalSize']);
        } else {
            $dynamicModal.find('.modal-dialog').addClass('modal-md');
        }

        $dynamicModal.modal('show');
        $spinner.addClass('hidden invisible');

        return true;
    }

    if (response['type'] === 'modal_remote') {
        if (response.hasOwnProperty('modalSize')) {
            $dynamicModal.find('.modal-dialog').addClass(response['modalSize']);
        } else {
            $dynamicModal.find('.modal-dialog').addClass('modal-md');
        }

        $dynamicModal.find('.modal-content').load(response.data, function() {
            $allModals.modal('hide');
            $dynamicModal.modal('show');
            $spinner.addClass('hidden invisible');
        });

        return true;
    }

    if (response['type'] === 'redirect') {
        if (response['data'] === 'reload') {
            window.location.reload(true);

            return true;
        }

        window.location.replace(response['data']);

        return true;
    }

    if (response['type'] === undefined) {
        location.reload();

        return true;
    }
}

function getNonFormElementData($form) {
    $.each($form.find('[data-get-value-source]'), function(_, element) {
        var $source = $('[id="' + $(element).data('get-value-source') + '"]');
        $(element).val($source.text());
    });
}
