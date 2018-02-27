require('../css/app.scss');
require('bootstrap/scss/bootstrap.scss');

var $ = require('jquery');
require('bootstrap');
require('font-awesome/scss/font-awesome.scss');

DASHTAINER = {};

DASHTAINER.formErrors = require('./formErrors');

$(document).ready(function() {
    require('./formAjax');
});
