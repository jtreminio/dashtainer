module.exports = function(element) {
    var ids = element.data('update-text').split(',');
    var val = element.val();

    $.each(ids, function(_, id) {
        var $target = $('[id="' + id + '"]');
        $target.text(val);

        if ($target.text() === '') {
            $target.text('* Needs Data');
        }
    });
};
