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

namespace App\Security;

/**
 * [Description CspNonceProvider]
 * Une seule valeur de nonce par requête, partagée entre le rendu Twig
 * (App\Twig\NonceAwareImportMapRenderer, injectée dans les balises <script>
 * inline d'asset-mapper) et l'en-tête Content-Security-Policy
 * (App\EventSubscriber\SecurityHeadersSubscriber) — les deux doivent porter
 * la même valeur pour que le navigateur autorise les scripts inline.
 *
 * asset-mapper rend `<script type="importmap">` et le point d'entrée
 * `<script type="module">import '...'</script>` en INLINE (vérifié dans
 * Symfony\Component\AssetMapper\ImportMap\ImportMapRenderer::render(), pas
 * de balise <script src="...">) — une CSP `script-src 'self'` sans nonce ni
 * `'unsafe-inline'` bloque donc silencieusement tout JS applicatif en
 * production/recette (où la CSP est active), sans aucune trace dans l'onglet
 * Réseau des DevTools (le blocage a lieu avant toute requête HTTP).
 *
 * Created at: 03/11/2026
 * @author     Laurent HADJADJ <laurent_h@me.com>
 * @copyright  Licensed Ma-Moulinette - GNU AGPL 3.0 ou ultérieure (AGPL-3.0-or-later).
 */
class CspNonceProvider
{
    private ?string $nonce = null;

    /**
     * [Description for getNonce]
     *
     * @return string
     *
     * Created at: 03/11/2026
     * @author     Laurent HADJADJ <laurent_h@me.com>
     * @copyright  Licensed Ma-Moulinette - GNU AGPL 3.0 ou ultérieure (AGPL-3.0-or-later).
     */
    public function getNonce(): string
    {
        return $this->nonce ??= bin2hex(random_bytes(16));
    }
}
