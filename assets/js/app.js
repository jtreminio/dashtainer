const imagesCtx = require.context('../images', true, /\.(png|jpg|jpeg|gif|ico|svg|webp)$/);
imagesCtx.keys().forEach(imagesCtx);

require('../scss/bundle.scss');
require('../scss/app.scss');

const $ = require('jquery');
global.$ = global.jQuery = $;

require('bootstrap');
require('font-awesome/scss/font-awesome.scss');

require('selectize');
require('selectize/dist/css/selectize.bootstrap3.css');

require('prismjs');
require('prismjs/themes/prism.css');
require('prismjs/components/prism-apacheconf');
require('prismjs/components/prism-docker');
require('prismjs/components/prism-ini');
require('prismjs/components/prism-javascript');
require('prismjs/components/prism-nginx');
require('prismjs/components/prism-yaml');

require('jquery-circle-progress');
require('jquery-mask-plugin');
require('jquery-sparkline');

//require('./tabler-core');

require('./tabToSpacesInput');

DASHTAINER = {};
DASHTAINER.formErrors = require('./formErrors');
DASHTAINER.eventDataToggleTab = require('./eventDataToggleTab');
DASHTAINER.misbehave = require('misbehave');
DASHTAINER.runMisbehave = require('./runMisbehave');
DASHTAINER.dataMask = require('./dataMask');

$(document).ready(function() {
    require('./formAjax');
    require('./addBlock');
    require('./removeBlock');
    require('./codeFromRemote');
    require('./card');

    DASHTAINER.eventDataToggleTab();
    DASHTAINER.dataMask();

    $.each($('pre[data-code-highlight]'), function(_, element) {
        DASHTAINER.runMisbehave(element);
    });

    $('[data-selectize-tags]').selectize({
        allowEmptyOption: true,
        create: true,
        plugins: ['remove_button'],
    });

    $(document).on('change keyup', '[data-update-text]', function(e) {
        var ids = $(this).data('update-text').split(',');
        var val = $(this).val();

        $.each(ids, function(_, id) {
            var $target = $('[id="' + id + '"]');
            $target.text(val);

            if ($target.text() === '') {
                $target.text('* Needs Data');
            }
        });
    });

    $(document).on('click', 'input[data-toggle="radio-tab"]', function(e) {
        var $target = $($(this).data('target'));
        $target.siblings().hide();
        $target.show();
    });
});
