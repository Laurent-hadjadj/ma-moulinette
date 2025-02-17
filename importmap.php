<?php

/**
 * Returns the importmap for this application.
 *
 * - "path" is a path inside the asset mapper system. Use the
 *     "debug:asset-map" command to see the full list of paths.
 *
 * - "entrypoint" (JavaScript only) set to true for any module that will
 *     be used as an "entrypoint" (and passed to the importmap() Twig function).
 *
 * The "importmap:require" command can be used to add new entries to this file.
 */
return [
    'errors' => [
        'path' => './assets/js/common/errors.js',
        'entrypoint' => true,
    ],
    'login' => [
        'path' => './assets/js/auth/login.js',
        'entrypoint' => true,
    ],
    'register' => [
        'path' => './assets/js/auth/register.js',
        'entrypoint' => true,
    ],
    'reset' => [
        'path' => './assets/js/auth/reset.js',
        'entrypoint' => true,
    ],
    'welcome' => [
        'path' => './assets/js/auth/welcome.js',
        'entrypoint' => true,
    ],
    'accueil' => [
        'path' => './assets/js/mon-application/accueil/index-accueil.js',
        'entrypoint' => true,
    ],
    'profil' => [
        'path' => './assets/js/mon-application/profil/index-profil.js',
        'entrypoint' => true,
    ],
    'profil-details' => [
        'path' => './assets/js/mon-application/profil/details.js',
        'entrypoint' => true,
    ],
    'projet' => [
        'path' => './assets/js/mon-application/projet/index-projet.js',
        'entrypoint' => true,
    ],
    'cosui' => [
        'path' => './assets/js/mon-application/cosui/index-cosui.js',
        'entrypoint' => true,
    ],
    'suivi' => [
        'path' => './assets/js/mon-application/suivi/index-suivi.js',
        'entrypoint' => true,
    ],
    'owasp' => [
        'path' => './assets/js/mon-application/owasp/index-owasp.js',
        'entrypoint' => true,
    ],
    'repartition-module' => [
        'path' => './assets/js/mon-application/repartition-module/index-repartition-module.js',
        'entrypoint' => true,
    ],
    'activity' => [
        'path' => './assets/js/mon-application/activity/index-activity.js',
        'entrypoint' => true,
    ],
    'easyBatch' => [
        'path' => './assets/js/easy-admin/batch.js',
        'entrypoint' => true,
    ],
    'easyAdmin' => [
        'path' => './assets/js/easy-admin/admin.js',
        'entrypoint' => true,
    ],
    'footer-donnees-personnelles' => [
        'path' => './assets/js/footer/donnees-personnelles.js',
        'entrypoint' => true,
    ],
    'footer-mention-legal' => [
        'path' => './assets/js/footer/mention-legal.js',
        'entrypoint' => true,
    ],
    'footer-plan-du-site' => [
        'path' => './assets/js/footer/plan-du-site.js',
        'entrypoint' => true,
    ],
    'jquery' => [
        'version' => '3.7.1',
    ],
    'foundation-sites' => [
        'version' => '6.9.0',
    ],
    'foundation-sites/dist/css/foundation.min.css' => [
        'version' => '6.9.0',
        'type' => 'css',
    ],
    'motion-ui' => [
        'version' => '2.0.8',
    ],
    'motion-ui/dist/motion-ui.css' => [
        'version' => '2.0.8',
        'type' => 'css',
    ],
    'what-input' => [
        'version' => '5.2.12',
    ],
    'browser-update' => [
        'version' => '3.3.55',
    ],
    'chart.js' => [
        'version' => '4.4.7',
    ],
    '@kurkle/color' => [
        'version' => '0.3.4',
    ],
    'chartjs-plugin-datalabels' => [
        'version' => '2.2.0',
    ],
    'chart.js/helpers' => [
        'version' => '4.4.7',
    ],
    'chartjs-plugin-zoom' => [
        'version' => '2.2.0',
    ],
    'hammerjs' => [
        'version' => '2.0.8',
    ],
    'chance' => [
        'version' => '1.1.12',
    ],
    'es6-promise' => [
        'version' => '4.2.8',
    ],
    '@babel/runtime/helpers/typeof' => [
        'version' => '7.26.0',
    ],
    'fflate' => [
        'version' => '0.8.2',
    ],
    'html2canvas' => [
        'version' => '1.4.1',
    ],
    'jspdf' => [
        'version' => '2.5.2',
    ],
    'chartjs-adapter-date-fns' => [
        'version' => '3.0.0',
    ],
    'date-fns' => [
        'version' => '4.1.0',
    ],
    'date-fns/locale' => [
        'version' => '4.1.0',
    ],
];
