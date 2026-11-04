<?php

/*
 *  Ma-Moulinette
 *  --------------
 *  Copyright (c) 2021-2026.
 *  Laurent HADJADJ <laurent_h@me.com>.
 *  Licensed under the GNU AGPL 3.0 or later (AGPL-3.0-or-later).
 *  ---
 *  Vous pouvez obtenir une copie de la licence à l'adresse suivante :
 *  https://www.gnu.org/licenses/agpl-3.0.html
 */

namespace App\EventSubscriber;

use App\Security\CspNonceProvider;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\ResponseEvent;
use Symfony\Component\HttpKernel\KernelEvents;

/**
 * [Description SecurityHeadersSubscriber]
 *
 * Pose les en-têtes de sécurité HTTP absents jusqu'ici (CSP, anti-clickjacking,
 * X-Content-Type-Options) et retire la fuite de version PHP (X-Powered-By) —
 * écarts confirmés par un scan ZAP actif complet le 16/10/2026 (voir
 * mkDocs/docs/ma-moulinette/developpement/securite.md). `script-src` est
 * relâché uniquement en dev (`kernel.debug`) car la Web Debug Toolbar Symfony
 * injecte du JS inline sans nonce — elle est déjà absente en prod, donc la
 * politique réellement auditée n'est pas affaiblie.
 *
 * MODIF 03/11/2026 : BUG CORRIGÉ — `script-src 'self'` (sans nonce ni
 * `'unsafe-inline'`) bloquait en réalité TOUT le JS applicatif en
 * environnement PROD sans debug (CSP active). asset-mapper rend `<script
 * type="importmap">` et le point d'entrée `<script type="module">` en
 * INLINE (pas de `src="..."`, vérifié dans `ImportMapRenderer::render()`),
 * jamais couvert par `'self'`. Reproduit sur la page de login, bouton
 * « Afficher le mot de passe » totalement inopérant, aucune anomalie dans
 * l'onglet Réseau des DevTools — le blocage CSP a lieu avant toute requête
 * HTTP, contrairement à un 404.
 *
 * Fix : un nonce (voir `App\Security\CspNonceProvider`, une valeur par
 * requête) est ajouté à `script-src` ET injecté dans le corps de la réponse
 * HTML sur ces 2 balises précises, par remplacement ciblé (pas de nonce côté
 * Twig : `ImportMapRenderer`, qui les génère, est une classe `@final` du
 * composant asset-mapper — l'étendre pour lui faire porter un attribut
 * supplémentaire casserait la règle PHPStan `class.extendsFinalByPhpDoc`.
 * Le polyfill `es-module-shims` (3ᵉ bloc de script inline, sans rapport
 * avec `<script type="importmap">`/`type="module">`) est désactivé par
 * ailleurs (`config/packages/asset_mapper.yaml`, `importmap_polyfill: false`)
 * — inutile sur les navigateurs réellement ciblés, ça évite d'avoir à le couvrir aussi.
 */
class SecurityHeadersSubscriber implements EventSubscriberInterface
{
    public function __construct(
        private readonly bool $debug,
        private readonly CspNonceProvider $nonceProvider,
        // MODIF 04/11/2026 : interrupteur (services.yaml) — à ne passer à
        // `false` que si une autre couche devant l'application (reverse-proxy,
        // nginx) pose déjà sa propre CSP : deux CSP simultanées se neutralisent
        // (le navigateur applique leur intersection, jamais leur union), pire
        // que de n'en avoir aucune. Cas réel rencontré sur un déploiement : un
        // gabarit nginx hérité (voir docker/etc/nginx/sites-enabled/) posait
        // encore sa propre CSP statique en plus de celle-ci.
        private readonly bool $cspEnabled = true,
    ) {}

    /**
     * @return array<string, string>
     */
    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::RESPONSE => 'onKernelResponse',
        ];
    }

    /**
     * [Description for onKernelResponse]
     *
     * @param ResponseEvent $event
     *
     * @return void
     *
     * Created at: 21/10/2026 08:53:38 (Europe/Paris)
     * @author     Laurent HADJADJ <laurent_h@me.com>
     * @copyright  Licensed Ma-Moulinette - GNU AGPL 3.0 ou ultérieure (AGPL-3.0-or-later).
     */
    public function onKernelResponse(ResponseEvent $event): void
    {
        if (!$event->isMainRequest()) {
            return;
        }

        // Émis par PHP (expose_php) avant que Symfony ne construise sa
        // Response : absent de $response->headers, donc pas retirable via
        // headers->remove(). header_remove() fonctionne tant qu'il est
        // appelé avant l'envoi effectif des en-têtes (le cas ici).
        header_remove('X-Powered-By');

        $response = $event->getResponse();
        $headers = $response->headers;

        $headers->set('X-Content-Type-Options', 'nosniff');
        $headers->set('X-Frame-Options', 'DENY');

        // En production, applique la CSP restrictive.
        // En développement, ne pose pas de CSP depuis ce subscriber.
        if ($this->cspEnabled && !$this->debug) {
            $headers->set('Content-Security-Policy', $this->buildCsp());
            $this->injectScriptNonce($response);
        }
    }

    /**
     * [Description for injectScriptNonce]
     * Ajoute l'attribut `nonce` (même valeur que dans la CSP) sur toute
     * balise <script> du rendu qui n'en porte pas déjà un — pas seulement
     * les 2 balises inline générées par asset-mapper (`type="importmap"`/
     * `type="module"`). Nécessaire aussi pour les balises <script src="...">
     * rendues par du code vendor qu'on ne maîtrise pas (ex. EasyAdmin) :
     * avec `'strict-dynamic'` actif, l'allow-listing par origine ('self')
     * est désactivé par la CSP elle-même, seul un nonce/hash fait encore foi
     * — voir le docblock de classe pour le pourquoi de cette approche par
     * remplacement plutôt que par décoration de `ImportMapRenderer`
     * (`@final`).
     *
     * @param Response $response
     *
     * @return void
     *
     * Created at: 03/11/2026
     * @author     Laurent HADJADJ <laurent_h@me.com>
     * @copyright  Licensed Ma-Moulinette - GNU AGPL 3.0 ou ultérieure (AGPL-3.0-or-later).
     */
    private function injectScriptNonce(Response $response): void
    {
        // MODIF 03/11/2026 : BUG CORRIGÉ — la garde sur Content-Type empêchait
        // systématiquement le remplacement. `Response::prepare()` (qui pose le
        // Content-Type par défaut `text/html; charset=...`) est appelé par le
        // listener natif `ResponseListener`, sur ce même événement
        // `kernel.response`, à la même priorité (0) que ce subscriber — selon
        // l'ordre d'exécution réel des deux listeners, ce subscriber peut
        // s'exécuter AVANT que le Content-Type ne soit posé, donc toujours vide
        // à cet instant. Remplacement par un contrôle sur le contenu lui-même
        // (inoffensif sur une réponse non HTML : le motif recherché n'y
        // apparaît jamais).
        $content = $response->getContent();
        if ($content === false || $content === '') {
            return;
        }

        // MODIF 04/11/2026 : BUG CORRIGÉ — ne cibler que les 2 balises
        // asset-mapper laissait bloquées toutes les autres balises <script>
        // du rendu (ex. <script src="/bundles/easyadmin/app.js">, injectée
        // par le template vendor d'EasyAdminBundle) : confirmé en conditions
        // réelles, violation CSP "strict-dynamic ... host-based allowlisting
        // is disabled" sur le back-office. `strict-dynamic` désactive
        // entièrement l'allow-listing par origine ('self' compris) — seul un
        // nonce/hash fait encore foi pour qu'une balise <script> s'exécute.
        $nonce = $this->nonceProvider->getNonce();
        $withNonce = preg_replace('/<script(?![^>]*\bnonce=)/i', "<script nonce=\"{$nonce}\"", $content);
        if ($withNonce !== null) {
            $content = $withNonce;
        }

        $response->setContent($content);
    }

    private function buildCsp(): string
    {
        // MODIF 03/11/2026 : nonce partagé avec injectScriptNonce() ci-dessus
        // (même instance de CspNonceProvider pour toute la requête) — sans lui,
        // les <script type="importmap">/<script type="module"> inline générés
        // par asset-mapper sont bloqués par 'self' seul (voir docblock de classe).
        $nonce = $this->nonceProvider->getNonce();

        return implode('; ', [
            "default-src 'self'",
            // 'strict-dynamic' : AssetMapper charge les CSS importées en JS via un
            // faux module "data:application/javascript,..." (voir ImportMapRenderer,
            // mécanisme interne, pas une ressource "'self'") — sans 'strict-dynamic',
            // ce chargement est bloqué par la CSP. Un script porteur du nonce propage sa
            // confiance à tout ce qu'il charge, quel que soit le schéma/l'origine ;
            // les navigateurs qui ne supportent pas 'strict-dynamic' retombent sur
            // 'self' (jeton ignoré, liste d'origines toujours active).
            "script-src 'self' 'nonce-{$nonce}' 'strict-dynamic'",
            //"script-src {$scriptSrc}",
            "style-src 'self' 'unsafe-inline'",
            "img-src 'self' data:",
            "object-src 'none'",
            "base-uri 'self'",
            "form-action 'self'",
            "frame-ancestors 'none'",
        ]);
    }
}
