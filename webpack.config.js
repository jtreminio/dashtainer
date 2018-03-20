var Encore = require('@symfony/webpack-encore');

Encore
    .setOutputPath('public/build/')
    .setPublicPath('/build')
    .addEntry('app', './assets/js/app.js')
    .enableSassLoader(function(sassOptions) {}, {
        resolveUrlLoader: false
    })
    .autoProvidejQuery({
        $: 'jquery',
        jQuery: 'jquery'
    })
    .enableSourceMaps(!Encore.isProduction())
    .cleanupOutputBeforeBuild()
    .enableVersioning(Encore.isProduction())
    .enableBuildNotifications()
;

if (Encore.isProduction()) {
    Encore.configureFilenames({
        images: '[path][name].[hash:8].[ext]',
        fonts: '[path][name].[hash:8].[ext]'
    });
} else {
    Encore.configureFilenames({
        images: '[path][name].[ext]',
        fonts: '[path][name].[ext]'
    });
}

module.exports = Encore.getWebpackConfig();
