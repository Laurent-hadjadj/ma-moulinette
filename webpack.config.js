const Encore = require('@symfony/webpack-encore');

if (!Encore.isRuntimeEnvironmentConfigured()) {
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
    .addEntry('home', './assets/js/app-home.js')
    .addEntry('projet', './assets/js/app-projet.js')
    .addEntry('profil', './assets/js/app-profil.js')
    .addEntry('owasp', './assets/js/app-owasp.js')

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

    .configureBabel((config) => {
        config.plugins.push('@babel/plugin-proposal-class-properties');
    })

    // enables @babel/preset-env polyfills
    .configureBabelPresetEnv((config) => {
        config.useBuiltIns = 'usage';
        config.corejs = 3;
    })

    // copy les images dans build/images depuis asset/images
    .copyFiles({
        from: './assets/images',
        to: '[path][name].[ext]',
        context: './assets'
    })
    // copy les favicon dans build/favicon depuis asset/favicon
    .copyFiles({
        from: './assets/favicon',
        to: '[path][name].[ext]',
        context: './assets'
    })
    // copy les webfonts dans /build/polices depuis assets/polices
    /*.copyFiles({
        from: './assets/fonts',
        to: '[path][name].[ext]',
        context: './assets'
    })*/

module.exports = Encore.getWebpackConfig();
