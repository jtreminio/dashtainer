module.exports = function(element) {
    let code = $(element).find('code')[0];
    let misbehave = new DASHTAINER.misbehave(code, {
        oninput : () => Prism.highlightElement(code)
    });

    $(element).on('click', function() {
        code.focus();

        return false;
    });
};
