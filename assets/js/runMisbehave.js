module.exports = function(element) {
    let code = $(element).find('code')[0];
    let misbehave = new DASHTAINER.misbehave(code, {
        oninput : () => Prism.highlightElement(code)
    });

    var $pre = $(element);

    if (!$(element).is('pre')) {
        $pre = $(element).find('pre');
    }

    $pre.on('click', function() {
        code.focus();

        return false;
    });
};
