module.exports = function(element) {
    var domElement = document;

    if (element) {
        domElement = element;
    }

    $(domElement).find('[data-mask-type="dns"]').mask('X'.repeat(64), {'translation':{
        'X': {pattern: /^[a-zA-Z0-9\-]+$/}
    }});

    $(domElement).find('[data-mask-type="filename"]').mask('X'.repeat(64), {'translation':{
        'X': {pattern: /^[a-zA-Z0-9._\-]+$/}
    }});

    $(domElement).find('[data-mask-type="secret"]').mask('X'.repeat(64), {'translation':{
        'X': {pattern: /^[a-zA-Z0-9_\-]+$/}
    }});
};
