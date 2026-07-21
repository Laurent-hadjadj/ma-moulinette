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
    'reset' => [
        'path' => './assets/js/auth/reset.js',
        'entrypoint' => true,
    ],
    'admin-log' => [
        'path' => './assets/js/mon-application/admin-log/app-admin-log.js',
        'entrypoint' => true,
    ],
    'user-role-log' => [
        'path' => './assets/js/mon-application/admin-log/app-user-role-log.js',
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
    'mes-projets' => [
        'path' => './assets/js/mon-application/projet/mes-projets.js',
        'entrypoint' => true,
    ],
    'preference' => [
        'path' => './assets/js/mon-application/preference/index-preference.js',
        'entrypoint' => true,
    ],
    'actuator' => [
        'path' => './assets/js/mon-application/actuator/index-actuator.js',
        'entrypoint' => true,
    ],
    'actuator-ajouter' => [
        'path' => './assets/js/mon-application/actuator/ajouter-actuator.js',
        'entrypoint' => true,
    ],
    'owasp' => [
        'path' => './assets/js/mon-application/owasp/index-owasp.js',
        'entrypoint' => true,
    ],
    'dependency-check' => [
        'path' => './assets/js/mon-application/dependency-check/index-dependency-check.js',
        'entrypoint' => true,
    ],
    'dependency-check-executive' => [
        'path' => './assets/js/mon-application/dependency-check/index-dependency-check-executive.js',
        'entrypoint' => true,
    ],
    'dependency-check-dashboard' => [
        'path' => './assets/js/mon-application/dependency-check/index-dependency-check-dashboard.js',
        'entrypoint' => true,
    ],
    'dependency-check-history' => [
        'path' => './assets/js/mon-application/dependency-check/index-dependency-check-history.js',
        'entrypoint' => true,
    ],
    'dependency-check-kpi' => [
        'path' => './assets/js/mon-application/dependency-check/index-dependency-check-kpi.js',
        'entrypoint' => true,
    ],
    'dependency-check-mutualisables' => [
        'path' => './assets/js/mon-application/dependency-check/index-dependency-check-mutualisables.js',
        'entrypoint' => true,
    ],
    'clean-code' => [
        'path' => './assets/js/mon-application/clean-code/index-clean-code.js',
        'entrypoint' => true,
    ],
    'dependency-check-comparer' => [
        'path' => './assets/js/mon-application/dependency-check/index-dependency-check-comparer.js',
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
    'batch' => [
        'path' => './assets/js/mon-application/batch/index-batch.js',
        'entrypoint' => true,
    ],
    'profiling' => [
        'path' => './assets/js/mon-application/profiling/index-profiling.js',
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
    'easy-admin' => [
        'path' => './assets/js/easy-admin/admin.js',
        'entrypoint' => true,
    ],
    'easy-footer' => [
        'path' => './assets/js/easy-admin/footer.js',
        'entrypoint' => true,
    ],
    'easy-groupe-fonctionnel' => [
        'path' => './assets/js/easy-admin/groupe-fonctionnel.js',
        'entrypoint' => true,
    ],
    'admin-dashboard' => [
        'path' => './assets/js/easy-admin/admin-dashboard.js',
        'entrypoint' => true,
    ],
    'admin-stats' => [
        'path' => './assets/js/easy-admin/admin-stats.js',
        'entrypoint' => true,
    ],
    'statistique' => [
        'path' => './assets/js/mon-application/statistique/app-statistique.js',
        'entrypoint' => true,
    ],
    'statistique-utilisateur' => [
        'path' => './assets/js/mon-application/statistique/app-statistique-utilisateur.js',
        'entrypoint' => true,
    ],
    'statistique-projet' => [
        'path' => './assets/js/mon-application/statistique/app-statistique-projet.js',
        'entrypoint' => true,
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
        'version' => '3.3.63',
    ],
    'chart.js' => [
        'version' => '4.5.1',
    ],
    '@kurkle/color' => [
        'version' => '0.4.0',
    ],
    'chartjs-plugin-datalabels' => [
        'version' => '2.2.0',
    ],
    'chart.js/helpers' => [
        'version' => '4.5.1',
    ],
    'chartjs-plugin-zoom' => [
        'version' => '2.2.0',
    ],
    'hammerjs' => [
        'version' => '2.0.8',
    ],
    'chance' => [
        'version' => '1.1.13',
    ],
    'es6-promise' => [
        'version' => '4.2.8',
    ],
    '@babel/runtime/helpers/typeof' => [
        'version' => '7.29.2',
    ],
    'fflate' => [
        'version' => '0.8.3',
    ],
    'chartjs-adapter-date-fns' => [
        'version' => '3.0.0',
    ],
    'date-fns' => [
        'version' => '4.3.0',
    ],
    'date-fns/locale' => [
        'version' => '4.3.0',
    ],
    '@babel/runtime/helpers/slicedToArray' => [
        'version' => '7.29.2',
    ],
    'fast-png' => [
        'version' => '8.0.0',
    ],
    'iobuffer' => [
        'version' => '6.0.1',
    ],
    'pako' => [
        'version' => '2.1.0',
    ],
    'datatables.net' => [
        'version' => '2.3.8',
    ],
    'datatables.net-zf' => [
        'version' => '2.3.8',
    ],
    'datatables.net-zf/css/dataTables.foundation.min.css' => [
        'version' => '2.3.8',
        'type' => 'css',
    ],
    'jquery' => [
        'version' => '3.7.1',
    ],
];
