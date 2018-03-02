require('../css/app.scss');
require('bootstrap/scss/bootstrap.scss');

const $ = require('jquery');
global.$ = global.jQuery = $;
require('bootstrap');
require('font-awesome/scss/font-awesome.scss');

DASHTAINER = {};

DASHTAINER.formErrors = require('./formErrors');

$(document).ready(function() {
    require('./formAjax');
});
