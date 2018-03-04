require('../css/app.scss');
require('bootstrap/scss/bootstrap.scss');

const $ = require('jquery');
global.$ = global.jQuery = $;
require('bootstrap');
require('font-awesome/scss/font-awesome.scss');
require('jquery-mask-plugin');

require('selectize');
require('selectize/dist/css/selectize.bootstrap3.css');

require('prismjs');
require('prismjs/themes/prism.css');
require('prismjs/components/prism-apacheconf');
require('prismjs/components/prism-ini');
require('prismjs/components/prism-javascript');
let Misbehave = require('misbehave');

require('./tabToSpacesInput');

DASHTAINER = {};
DASHTAINER.formErrors = require('./formErrors');

$(document).ready(function() {
    require('./formAjax');

    $('[data-mask-type="dns"]').mask('X'.repeat(64), {'translation':{
        'X': {pattern: /^[a-zA-Z0-9\-]+$/}
    }});

    $.each($('pre[data-code-highlight]'), function(_, element) {
        let code = $(element).find('code')[0];
        let misbehave = new Misbehave(code, {
            oninput : () => Prism.highlightElement(code)
        });

        $(element).on('click', function() {
            code.focus();

            return false;
        });
    });

    $('[data-selectize-tags]').selectize({
        allowEmptyOption: true,
        create: true,
        plugins: ['remove_button'],
    });
});
