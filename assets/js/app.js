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
require('prismjs/components/prism-bash');
require('prismjs/components/prism-docker');
require('prismjs/components/prism-ini');
require('prismjs/components/prism-javascript');
require('prismjs/components/prism-nginx');
require('prismjs/components/prism-yaml');

require('jquery-circle-progress');
require('jquery-mask-plugin');
require('jquery-sparkline');
require('jdenticon');
require('font-mfizz/dist/font-mfizz.css');
require('floatthead');
Sticky = require('sticky-js/dist/sticky.compile.js');

require('./tabToSpacesInput');

DASHTAINER = {};
DASHTAINER.formErrors = require('./formErrors');
DASHTAINER.eventDataToggleTab = require('./eventDataToggleTab');
DASHTAINER.misbehave = require('misbehave');
DASHTAINER.runMisbehave = require('./runMisbehave');
DASHTAINER.dataMask = require('./dataMask');
DASHTAINER.updateTextTo = require('./updateTextTo');

$(document).ready(function() {
    require('./formAjax');
    require('./addElement');
    require('./addTab');
    require('./removeElement');
    require('./removeTab');
    require('./codeFromRemote');
    require('./card');

    DASHTAINER.eventDataToggleTab($('body'));

    $.each($('pre[data-code-highlight]'), function(_, element) {
        DASHTAINER.runMisbehave(element);
    });

    $('[data-selectize-tags]').selectize({
        allowEmptyOption: true,
        create: true,
        plugins: ['remove_button']
    });

    $(document).on('change keyup', '[data-update-text]', function(e) {
        DASHTAINER.updateTextTo($(this));
    });

    $(document).on('click', 'input[data-toggle="radio-tab"]', function(e) {
        var $target = $($(this).data('target'));
        $target.siblings().hide();
        $target.show();
    });

    $('[data-toggle="tooltip"]').tooltip({boundary: 'viewport'});

    $('table.table-scroll').floatThead({
        scrollContainer: function ($table) {
            return $table.closest('.scrollable-container');
        }
    });

    $(document).on('click', '[data-target="#advanced-options"]', function(e) {
        $.each($('#advanced-options').find('table.table-scroll'), function(_, element) {
            $(this).floatThead('reflow');
        });
    });

    new Sticky('[data-sticky]', {
        marginTop: 130,
    });
});
