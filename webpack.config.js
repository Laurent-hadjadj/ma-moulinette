// eslint-disable-next-line no-undef
const Encore = require('@symfony/webpack-encore');

if (!Encore.isRuntimeEnvironmentConfigured()) {
    // eslint-disable-next-line no-undef
    Encore.configureRuntimeEnvironment(process.env.NODE_ENV || 'dev');
}

Encore
    .setOutputPath('public/build/')
    .setPublicPath('/build')
    .autoProvidejQuery()

    /*
     * ENTRY CONFIG
     *
     */
    .addEntry('errors', './assets/js/errors.js')
    .addEntry('login', './assets/js/app-login.js')
    .addEntry('register', './assets/js/app-register.js')
    .addEntry('welcome', './assets/js/app-welcome.js')
    .addEntry('home', './assets/js/app-home.js')
    .addEntry('plan-du-site', './assets/js/app-footer-plan.js')
    .addEntry('preference', './assets/js/app-preference.js')
    .addEntry('preference-ajouter', './assets/js/app-preference-ajouter.js')
    .addEntry('projet', './assets/js/app-projet.js')
    .addEntry('profil', './assets/js/app-profil.js')
    .addEntry('profil-details', './assets/js/app-profil-details.js')
    .addEntry('owasp', './assets/js/app-owasp.js')
    .addEntry('suivi', './assets/js/app-suivi.js')
    .addEntry('repartition', './assets/js/app-repartition.js')
    .addEntry('cosui', './assets/js/app-cosui.js')
    .addEntry('batch', './assets/js/app-batch.js')
    .addStyleEntry('easyAdmin', './assets/styles/admin.css')
    .addStyleEntry('easyBatch', './assets/styles/batch.css')

    // When enabled, Webpack "splits" your files into smaller pieces for greater optimization.
    .splitEntryChunks()

    // will require an extra script tag for runtime.js
    // but, you probably want this, unless you're building a single-page app
    .enableSingleRuntimeChunk()

    /*
     * FEATURE CONFIG
     *
     */
    .cleanupOutputBeforeBuild()
    .enableBuildNotifications()
    .enableSourceMaps(!Encore.isProduction())
    .enableVersioning(Encore.isProduction())

    .configureBabel(config => {
        //deprecated : config.plugins.push('@babel/plugin-proposal-class-properties');
        config.plugins.push('@babel/plugin-transform-class-properties');
    })

    // enables @babel/preset-env polyfills
    .configureBabelPresetEnv(config => {
        config.useBuiltIns = 'usage';
        config.corejs = 3;
    })

    // copy les images dans build/images depuis asset/images
    .copyFiles({
        from: './assets/images',
        to: '[path][name].[ext]',
        context: './assets'
    })
    // copy les images dans build/avatar depuis asset/avatar
    .copyFiles({
        from: './assets/avatar',
        to: '[path][name].[ext]',
        context: './assets'
    })
    // copy les favicon dans build/favicon depuis asset/favicon
    .copyFiles({
        from: './assets/favicon',
        to: '[path][name].[ext]',
        context: './assets'
    })
    // copy les videos dans build/video depuis asset/video
    .copyFiles({
        from: './assets/video',
        to: '[path][name].[ext]',
        context: './assets'
    });

// eslint-disable-next-line no-undef
module.exports = Encore.getWebpackConfig();
