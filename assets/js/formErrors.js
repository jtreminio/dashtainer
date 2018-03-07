module.exports = function(errors) {
    // Clear out old errors if any
    $('.is-invalid').removeClass('is-invalid');
    $('.invalid-feedback').html('');

    if (errors === undefined || errors.length === 0) {
        return false;
    }

    /*
     * Clear existing errors on first run. Errors could exist
     * if user has submitted form more than once and has had
     * validation errors on all attempts.
     */
    var clearExistingErrors = true;

    var errorsMerged = {};

    $.each(errors, function(element, messages) {
        var exploded = element.split(',');

        for (var i = 0; i < exploded.length; ++i) {
            errorsMerged[exploded[i]] = messages;
        }
    });

    $.each(errorsMerged, function(element, messages) {
        // First search for form field element by its name="{element}" attribute
        var $formElementByName = $('[name="' + element + '"]');
        // If form field element not found by name attribute, search by id="{element}"
        var $formElementById   = $('[id="' + element + '"]');
        var $formElement       = ($formElementByName.length > 0)
            ? $formElementByName
            : $formElementById;

        var errContName     = $formElement.data('error-container');
        var $errorContainer = $('[data-error-container-name="' + errContName + '"]');

        // Remove error states for elements when clicked on
        $formElement.on('click', function (e) {
            $(this).removeClass('is-invalid');
            $(this).children().removeClass('is-invalid');
        });

        // If no error container has been found, do not display errors
        if (!$errorContainer.length) {
            return;
        }

        $formElement.addClass('is-invalid');

        // Clear existing error messages
        if (clearExistingErrors) {
            $errorContainer.find('.is-invalid').remove();

            clearExistingErrors = false;
        }

        $.each(messages, function(index, message) {
            var msg = '* ' + message + '<br>';

            // Already contains error message
            if ($errorContainer.html() !== undefined
                && $errorContainer.html().indexOf(msg) >= 0
            ) {
                return;
            }

            $errorContainer.append(msg);
            $errorContainer.show();
        });
    });
};
