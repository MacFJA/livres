var Encore = require('@symfony/webpack-encore');

Encore
    // the project directory where compiled assets will be stored
    .setOutputPath('public/build/')
    // the public path used by the web server to access the previous directory
    .setPublicPath('/build')
    .cleanupOutputBeforeBuild()
    .enableSourceMaps(false)
    // uncomment to create hashed filenames (e.g. app.abc123.css)
    // .enableVersioning(Encore.isProduction())

    // uncomment to define the assets of the project
    .addEntry('js/book', './assets/js/book.js')
    .addEntry('js/md-polyfill', 'md-gum-polyfill')
    .addEntry('js/search', './assets/js/search.js')
    .addEntry('js/completion', './assets/js/completion.js')
    .addEntry('js/scanner', './assets/js/scanner.js')
    .addEntry('js/settings', './assets/js/settings.js')
    .addStyleEntry('css/book', './assets/css/book.scss')
    .addStyleEntry('css/list', './assets/css/list.scss')
    .addStyleEntry('css/search', './assets/css/search.scss')
    .addStyleEntry('css/settings', './assets/css/settings.scss')

    // uncomment if you use Sass/SCSS files
    .enableSassLoader()

    // uncomment for legacy applications that require $/jQuery as a global variable
    // .autoProvidejQuery()
;

module.exports = Encore.getWebpackConfig();

