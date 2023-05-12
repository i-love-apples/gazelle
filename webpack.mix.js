let mix = require('laravel-mix');

let sassopts = {
    sassOptions: {
        outputStyle: mix.inProduction() ? 'compressed' : 'expanded',
    }
};

mix.disableSuccessNotifications()
    .setPublicPath('.')
    .options({
        processCssUrls: false,
    })
    .sass('sass/80char/style.scss',             'public/static/styles/80char',             sassopts)
    .sass('sass/apollostage/style.scss',        'public/static/styles/apollostage',        sassopts)
    .sass('sass/apollostage_coffee/style.scss', 'public/static/styles/apollostage_coffee', sassopts)
    .sass('sass/apollostage_sunset/style.scss', 'public/static/styles/apollostage_sunset', sassopts)
    .sass('sass/ianon_imperial/style.scss',     'public/static/styles/ianon_imperial',     sassopts)
    .sass('sass/ianon_sky/style.scss',          'public/static/styles/ianon_sky',          sassopts)
    .sass('sass/ianon_light/style.scss',        'public/static/styles/ianon_light',        sassopts)
    .sass('sass/red_esteem/style.scss',         'public/static/styles/red_esteem',         sassopts)
    .sass('sass/voidbb/style.scss',             'public/static/styles/voidbb',             sassopts)
    .sass('sass/datetime_picker/style.scss',    'public/static/styles/datetime_picker',    sassopts)
    .sass('sass/public/style.scss',             'public/static/styles/public',             sassopts)
    .sass('sass/tiles/style.scss',              'public/static/styles/tiles',              sassopts)
    .sass('sass/tooltipster/style.scss',        'public/static/styles/tooltipster',        sassopts)
    .sass('sass/global.scss',                   'public/static/styles',                    sassopts)
    .sass('sass/log.scss',                      'public/static/styles',                    sassopts)
    .sass('sass/minimal_mod_alt.scss',          'public/static/styles',                    sassopts)
    .sass('sass/musicbrainz.scss',              'public/static/styles',                    sassopts)
;

if (mix.inProduction()) {
    mix.version();
} else {
    mix.sourceMaps(false, 'source-map');
    mix.browserSync({
        proxy: 'localhost:8080',
        notify: false,
        files: [
            'public/static/styles/**/*.css',
            'public/static/functions/**/*.js',
            'templates/**/*.twig',
        ],
    });
}
