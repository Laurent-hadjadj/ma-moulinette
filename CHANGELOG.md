# Changelog

Toutes les évolutions notables de Ma-Moulinette sont documentées dans ce fichier.

Le format est basé sur [Keep a Changelog](https://keepachangelog.com/fr/1.1.0/), et ce projet adhère (au mieux, l'historique antérieur à cette convention restant dans son style d'origine) au [Semantic Versioning](https://semver.org/lang/fr/).

## [2.6.0] - 04-11-2026

### Corrigé

- **Toutes les pages (prod/recette)** : La CSP `script-src 'self'` bloquait en réalité tout le JavaScript applicatif en environnement de PROD — asset-mapper rend `<script type="importmap">` et le point d'entrée `<script type="module">` en inline (pas de `src="..."`), jamais couvert par `'self'` seul. Reproduit sur la page de login (bouton « Afficher le mot de passe » totalement inopérant), sans aucune anomalie dans l'onglet Réseau des DevTools (le blocage CSP d'un script inline n'émet aucune requête). Un nonce par requête (`CspNonceProvider`) est désormais ajouté à `script-src` et injecté dans les 2 balises concernées ; le polyfill `es-module-shims` (3ᵉ bloc de script inline, conditionnel) est supprimé plutôt que couvert, inutile sur les navigateurs réellement ciblés (`importmap_polyfill: false`). Vérification en conditions réelles (Chromium headless, pas une hypothèse) : 2 bugs supplémentaires trouvés et corrigés avant mise en recette — une garde sur `Content-Type` empêchait toujours l'injection du nonce (en-tête pas encore posé à cet instant, `Response::prepare()` et ce subscriber partagent le même événement `kernel.response` à la même priorité) ; `'strict-dynamic'` a dû être ajouté à `script-src`, le nonce seul ne couvrant pas le chargement interne des CSS importées en JS par asset-mapper (`data:application/javascript,...`). Déployé en recette, un 3ᵉ bug est apparu sur le back-office : `'strict-dynamic'` désactive tout allow-listing par origine (y compris `'self'`), donc une balise `<script src="...">` classique sans nonce — ex. `/bundles/easyadmin/app.js`, rendue par du code vendor qu'on ne maîtrise pas — reste bloquée. `injectScriptNonce()` ajoute désormais le nonce à **toute** balise `<script>` du rendu qui n'en porte pas déjà un, plutôt qu'aux 2 seules balises inline d'asset-mapper. Un 4ᵉ bug, celui-là réellement côté infrastructure et non applicatif, a ensuite été identifié en déploiement réel : le gabarit nginx fourni (`docker/etc/nginx/sites-enabled/prod_ma_moulinette`) pose ses propres `X-Frame-Options`/`X-Content-Type-Options`/`X-XSS-Protection`/`Content-Security-Policy` statiques, en plus de ceux posés par `SecurityHeadersSubscriber` — avec deux `Content-Security-Policy` reçues, le navigateur applique leur **intersection** (jamais leur union), et la CSP statique de nginx (`script-src 'self'` sans nonce) neutralisait systématiquement celle, correcte, de l'application. Confirmé en isolant totalement la cause (CSP applicative désactivée puis retirée entièrement : le blocage persistait exactement aux mêmes lignes). Les 4 `add_header` du gabarit nginx sont désormais commentés (pas supprimés) ; la responsabilité des en-têtes de sécurité HTTP revient entièrement à l'application. Un interrupteur `$cspEnabled` (`services.yaml`, `true` par défaut) permet de désactiver la CSP applicative sans toucher au code si une autre couche en pose une — utile pour isoler ce genre de conflit sans devoir choisir entre « tout retirer » et « tout garder ». `.gitlab-ci.yml` : le script de déploiement ne recréait jamais `var/sessions/` après un déploiement (`var/` est entièrement ignoré par git, ce dossier ne survivait que par accident, protégé par une erreur de permission sur l'ancien cache qui empêchait le nettoyage complet) — `mkdir -p`/`chmod 777` ajoutés après l'extraction, et un `docker restart php-fpm-ma-moulinette` documenté en commentaire (désactivé par défaut, à activer si le cache applicatif redevient périmé malgré les `chmod`).
- **Back-office › Utilisateurs** : Un gestionnaire pouvait supprimer son propre compte depuis le CRUD EasyAdmin — la session restait authentifiée après la suppression de la ligne en base, et la requête suivante plantait (`EntityUserProvider::refreshUser()` ne retrouve plus l'identifiant : « You cannot refresh a user from the EntityUserProvider that does not contain an identifier. »). Reproduit en conditions réelles. `UtilisateurCrudController::deleteEntity()` compare désormais le courriel de la cible à celui de l'éditeur connecté et bloque la suppression (message flash), quel que soit son rôle.
- **Page Suivi** : La page renvoyait une erreur 500 (`SQLSTATE[42703] column "eco_rating" does not exist`) sur toute base dont la table `historique` avait été peuplée uniquement via les scripts de migration idempotents (pas via un install complet) — `migrations/POSTGRESQL/updates/08_alter_historique_eco_conception.sql` n'a jamais ajouté que 2 des 15 colonnes `eco_*` (Empreinte Écologique) depuis sa création, et son test d'existence portait sur une des 2 colonnes déjà présentes, ce qui faisait sauter tout le script sans jamais ajouter le reste, même rejoué. Les 13 colonnes manquantes sont ajoutées, le test d'idempotence porte désormais sur `eco_rating` (la colonne réellement absente).
- **Page Mes Projets / Statistiques Projet** : Audit des couleurs `!important` demandé par Sonar — 6 `!important` retirés (aucune règle à surcharger, ou spécificité déjà suffisante), 4 classes passées en sélecteur doublé plutôt que `!important` (`.couleur-vert`/`.orange`/`.rouge`/`.bordeaux`, posées en JS par-dessus des règles de même spécificité chargées après elles selon la page), 1 conservé volontairement et documenté (`.clicked-true`/`.clicked-false`, nécessaire pour surcharger un sélecteur ID).
- **Plusieurs pages** (OWASP, ZAP, Suivi, Préférences, Profil, Projet, Répartition par module) : `#c45d4e` (accent hover « danger »/suppression, et libellé de sévérité `.stats-list-orange`) échouait le contraste WCAG AA en texte sur fond clair (4,18:1, sous le seuil 4,5:1) — remplacé par `#ba5705` (rôle « texte orange », déjà la référence de l'application) ou `#bf0815` (rôle « texte/hover rouge »), selon l'usage réel de chaque occurrence.
- **Page Clean Code** : Le badge de niveau de risque (`.badge-level`) n'avait jamais de règle dans `clean-code.css` (la seule feuille importée par cette page) ni dans `common.css` — rendu sans aucun style depuis l'origine de la page. Portée depuis `dependency-check.css`, où la même convention existe déjà.
- **Page Dependency-Check** : Deux boutons (`#bouton-ouvrir-dc-index`, `#bouton-ouvrir-dc-history`) n'avaient jamais eu de règle dans le groupe de sélecteurs partagé de `dependency-check.css` — rendus sans style (base et `:hover`) depuis leur ajout.
- **Page Projet** : Le journal de collecte affichait toujours « (14) » pour l'étape finale (« La collecte des données est terminée »), numéro copié d'une étape antérieure — corrigé en « (17) », cohérent avec la numérotation réelle des étapes précédentes.
- **Toutes les pages** : Trouvés en vérifiant le correctif CSP ci-dessus en conditions réelles (Chromium headless) — 2 favicons jamais générés (`favicon/apple-touch-icon.png`, doublon d'une icône 180x180 déjà présente sous un autre nom ; `favicon/safari-pinned-tab.svg`, aucune source monochrome disponible) retirés de `head.html.twig` plutôt que fabriqués. Modale de choix d'avatar (incluse sur toutes les pages via `header.html.twig`) : l'avatar « chiffre 1 » référençait `avatar/chiffre/1.png`, absent du disque (seul `01.png` existe) — AssetMapper ne pouvant pas résoudre le chemin, le `<img>` pointait silencieusement vers l'URL brute, non servie. Même défaut (concaténation `~ i ~` incomplète) corrigé par anticipation dans `register.html.twig`, sans conséquence actuelle (boucle à une seule itération).

### Ajouté

- **Page Clean Code** : Les 3 tuiles « Impact qualité logicielle » (Maintenabilité/Fiabilité/Sécurité) affichent désormais la pire sévérité rencontrée pour chaque dimension (`maintainability_issue_severity`/`reliability_issue_severity`/`security_issue_severity`, métriques optionnelles SonarQube 26.3.0-26.9.0, collectées mais jamais affichées — leur sémantique n'était alors pas documentée). Confirmée via l'API SonarQube (`/api/metrics/search` : « Worst severity of {dimension} issues ») et par recoupement avec un projet (`/api/issues/search`, facette `impactSeverities`) : encodage 0/5/10/15/20/25 = aucune issue/INFO/LOW/MEDIUM/HIGH/BLOCKER.
- **Pages Actuator, Dependency-Check, ZAP** : Lien « Projet » ajouté au fil d'Ariane, entre « Accueil » et la page courante — raccourci direct, évite de repasser par la page d'accueil pour revenir sur le projet en cours.

### Modifié

- **Licence** : le code passe de CC BY-NC-SA 4.0 (licence non reconnue comme open source, sa clause « pas d'usage commercial » étant incompatible avec cette définition) à **AGPL 3.0 ou ultérieure** — licence à copyleft dont la clause réseau garantit que le logiciel reste ouvert même proposé comme service hébergé. La documentation (`README.md`, `mkDocs/docs/`) passe en CC BY-SA 4.0. Les versions publiées jusqu'à la 2.5.0 incluse restent sous l'ancienne licence (irrévocable) ; son texte est conservé dans `LICENSES/CC-BY-NC-SA-4.0.txt`. Détail et portée du changement : [LICENSING.md](LICENSING.md).

### Documentation

- **Page Répartition** : Mise à jour de la description du verrou de session du bouton « Historique », qui décrivait encore son intention d'origine (corrigée le 30/10/2026 — seule une vraie erreur serveur le déclenche désormais, plus le 404 normal « pas encore de données historisées »).
- **Architecture base de données** : Ajout des tables `owasp_details` et `accessibility`, absentes depuis leur création respective ; compteur de tables mis à jour (52 → 54).
- **Page Actuator** : Ajout du chemin de fer (absent jusqu'ici, contrairement aux autres pages applicatives) pour la liste `/actuator` et pour le formulaire partagé `/actuator/info`/`/actuator/{id}/modifier`.
- **Page ZAP** : Ajout du chemin de fer, absent jusqu'ici, y compris le tableau « Détail des alertes » et ses modales, jamais mentionnés dans la section « Contenu de la page » existante.
- **Gestion de la sécurité** : Table des en-têtes HTTP et nouvelle note décrivant le bug CSP/asset-mapper ci-dessus (cause, vérification, correctif).

## [2.5.0] - 01-11-2026

### Ajouté

- **Page OWASP** : Nouveau service `OwaspRuleClassifierService`, qui classe localement les vulnérabilités OWASP par catégorie (A1-A10, référentiels 2017/2021) à partir d'une table de correspondance règle → catégorie (65 règles SonarSource lues depuis les métadonnées officielles du plugin Java, 61 règles du plugin tiers FindSecBugs lues depuis leur tag `owasp-aX`).
- **Page OWASP** : Nouveau tableau de détail des 10 vulnérabilités OWASP les plus critiques (triées par sévérité : Bloquant > Critique > Majeur > Mineur), avec le nombre total réel affiché en dessous — jusqu'ici, le seul tableau de détail de la page ne listait que les Security Hotspots, jamais les vulnérabilités OWASP elles-mêmes. Nouvelle table `owasp_details` (une ligne par vulnérabilité classée et par référentiel, snapshot réécrit à chaque collecte comme `owasp`), alimentée au passage dans la collecte existante : `line`/`message` étaient déjà présents dans la réponse SonarQube déjà interrogée, simplement jamais capturés jusqu'ici, donc aucun appel réseau supplémentaire. L'export PDF est réorganisé en conséquence : une page dédiée à ce nouveau tableau, placée avant la page des Security Hotspots (déjà renommée le 27/10 pour clarifier qu'elle ne couvre jamais les vulnérabilités OWASP elles-mêmes).
- **Page Projet** : Le sélecteur de projet affiche désormais une pastille verte (« Collecté ») ou grise (« Jamais collecté ») à côté de chaque projet, pour savoir avant de cliquer sur « Peinture » si des données existent déjà — jusqu'ici, la seule façon de le savoir était de cliquer et d'obtenir un message d'erreur en l'absence de collecte préalable. Le signal repose sur `information_projet`, la toute première table peuplée par n'importe quelle collecte (interactive ou batch), avant tout autre indicateur. La pastille du projet qui vient d'être collecté bascule au vert immédiatement après succès, sans recharger la page ni refaire d'appel réseau.

### Corrigé

- **Page OWASP** : La facette officielle SonarQube (`owaspTop10`/`owaspTop10-2021`) ne classe jamais les règles du plugin tiers FindSecBugs (elles ne portent qu'un tag texte libre, jamais le bloc structuré `securityStandards` que lit la facette) — vérifié en direct sur une instance SonarQube réelle : 453 vulnérabilités d'injection SQL détectées par FindSecBugs sur un projet, 0 d'entre elles visibles via la facette. Le mécanisme de secours existant (classification par catégorie, un appel SonarQube par catégorie) ne récupérait que les compteurs globaux, jamais le détail par sévérité d'une catégorie classée uniquement par FindSecBugs (tableau de détail vide côté page malgré un total non nul). Remplacé par un appel unique, paginé, filtré sur la liste complète des règles couvrant OWASP (core + FindSecBugs), classées localement — `BatchCollecteOwaspController` ne fait plus qu'un seul appel SonarQube pour les vulnérabilités (hors Security Hotspots, toujours classés comme avant, non concernés par ce défaut).

### Modifié

- **Page OWASP** : La table `owasp` remplace ses 60 colonnes quasi identiques (`a1`..`a10` + 5 niveaux de sévérité chacune) et la colonne `source` par une seule colonne JSON `categories` (sparse, uniquement les catégories réellement touchées) — simplification du schéma, et la provenance de la classification (règle SonarSource, FindSecBugs, ou les deux) est désormais correcte par catégorie plutôt qu'approximée au niveau de la ligne entière.
- **Page OWASP** : Nouveau champ de répartition par module (`module_frontend`/`module_backend`/`module_autre`) sur les vulnérabilités OWASP, classées par la même méthode que RCI (regex sur le chemin du fichier) — remplace la répartition par module affichée jusqu'ici, basée sur les hotspots uniquement.
- **Page OWASP** : Bloc d'informations en tête de page restructuré : deux petites tuiles compteur (total + note A-E, Vulnérabilités OWASP et Hotspot) affichées en premier, suivies des deux tuiles de détail par sévérité — au lieu d'un seul bloc mélangeant vulnérabilités et hotspots. Nouvelle note A-E pour les vulnérabilités OWASP (jusqu'ici seul Hotspot en avait une), calculée sur la pire sévérité présente (Bloquant > Critique > Majeur > Mineur), même convention visuelle `.stat-note`/`note-vertX` que les tuiles Accessibilité/RCI/Empreinte Écologique de la page Projet (le tableau de synthèse A1-A10 garde son badge existant, inchangé). Nouveau callout indiquant le nombre de règles (SonarQube + FindSecBugs) couvrant le référentiel actif.
- **Page OWASP** : Les données du référentiel par défaut (2021 si disponible, sinon 2017) s'affichent désormais dès le chargement de la page, sans avoir à cliquer sur un bouton de référentiel au préalable ; les 3 boutons restent utilisables pour changer de référentiel ensuite.

### Corrigé (suite, vérification manuelle)

- **Page OWASP** : Le texte « Référentiel OWASP Actuel » était partiellement masqué par les tuiles d'informations qui le suivent directement (`margin-bottom` négatif hérité de l'ancienne mise en page à un seul fieldset) ;
- **Page OWASP** : Dans la modale de détail d'une catégorie, les badges de sévérité des vulnérabilités (Bloquant/Critique/Majeur) réutilisaient les couleurs à 3 niveaux des hotspots (Élevée/Moyenne/Faible) ; Mineur n'avait aucune couleur du tout (pas de pastille, désalignement visuel avec les 3 autres) — 4ᵉ couleur ajoutée (vert clair), complétant l'échelle A-E déjà utilisée ailleurs sur la page ;
- **Page OWASP** : Sur un projet sans aucun Security Hotspot classé OWASP, la colonne Hotspot du tableau de synthèse A1-A10 affichait un badge bleu (couleur par défaut de Foundation) au lieu d'un badge vert — le repli par défaut utilisait la classe littérale `a` au lieu de `note-a` (préfixe utilisé par toutes les autres couleurs de note de l'application), donc une classe CSS qui ne correspond à aucune règle ;
- **Page OWASP** : Le calcul de la note A-E des hotspots (`calculNoteHotspot()`, colonne Hotspot du tableau A1-A10) avait des trous aux valeurs de taux exactement égales à 0,31/0,51/0,71/0,81 — aucune des bornes (`>`/`<` strictes, pas de recouvrement) ne matchait ces valeurs précises, laissant le badge sans couleur (bleu par défaut de Foundation). Réécrit en cascade sans trou ;
- **Page OWASP** : Dans le tableau de synthèse A1-A10, un nombre de failles/hotspots à 2 chiffres (≥10) décalait le badge de note vers la droite par rapport aux lignes à 1 chiffre (le nombre n'avait pas de largeur fixe) — largeur minimale + alignement à droite ajoutés ;
- **Page OWASP** : Dans la modale de détail d'une catégorie, `.stat-list` utilisait `text-align: center` sur des éléments `display: inline-block`, ce qui ne les répartissait pas en colonnes correctement alignées (décalage visible entre les 4 colonnes Vulnérabilités et les 3 colonnes Hotspots). Remplacé par `display: grid` / `grid-auto-flow: column` ; la modale elle-même repasse de `reveal large` à `reveal small` (la largeur large n'était nécessaire que pour compenser l'ancien alignement défaillant) ;
- **Page OWASP** : Plusieurs clics rapprochés sur l'icône 🔎 d'une catégorie (le temps que l'appel AJAX réponde) empilaient autant de gestionnaires asynchrones en parallèle, chacun rouvrant/refermant la modale de détail à la résolution de sa propre promesse — jusqu'à une dizaine d'ouvertures/fermetures en rafale pour une dizaine de clics. Garde anti-double-clic ajoutée (les clics reçus pendant un chargement en cours sont ignorés) ; la modale s'ouvre désormais immédiatement avec un indicateur de chargement, plutôt que d'attendre la fin de l'appel avant de s'ouvrir ;
- **Page OWASP** : Le titre et la légende du tableau détaillé en bas de page (« Liste des failles de sécurité ») laissaient croire qu'il couvrait les vulnérabilités OWASP, alors qu'il n'a jamais listé que les Security Hotspots (fonctionnalité SonarQube appelée à disparaître) — titre et légende précisés, aucun changement de données (les vulnérabilités OWASP n'ont pas de détail ligne à ligne, seulement des compteurs par catégorie, déjà visibles dans la modale de détail du tableau de synthèse).

### Corrigé (suite, nouveau tableau de détail des vulnérabilités OWASP)

- **Page OWASP** : La première ligne (A1) du tableau de synthèse A1-A10 était visiblement plus haute que les autres — son icône SVG portait une coquille (`viewlight` au lieu de `viewbox`) depuis l'origine du tableau, jamais remarquée tant que personne n'avait comparé les 10 lignes côte à côte. Sans `viewBox` valide, le SVG ne respecte pas ses dimensions déclarées et se redimensionne hors normes. Corrigé ;
- **Page OWASP** : La modale de détail d'une catégorie (répartition par sévérité des vulnérabilités, ajoutée le 26/10) n'affichait plus aucune donnée — un renommage d'identifiants effectué en parallèle sur la page (préfixe `js-`) n'avait été répercuté que côté modèle, jamais côté script, qui continuait à cibler les anciens identifiants ; les données étaient bien reçues du serveur mais jamais injectées dans la page ;
- **Page OWASP** : Même défaut sur le tableau détaillé des Security Hotspots après un renommage similaire de son corps de tableau — une coquille (« hotpot » au lieu de « hotspot ») sur 2 des 3 références au nouvel identifiant empêchait le tableau de s'afficher ;
- **Page OWASP** : Dans la tuile « Répartition par module », les nombres (Frontend/Backend/Autre) et leurs pourcentages n'avaient aucune largeur fixe — un module à 0 ne s'alignait pas sous un module à 2 chiffres, chaque ligne démarrant à une position différente selon le nombre de chiffres affichés par les deux lignes voisines. Largeur minimale et alignement à droite ajoutés sur les deux colonnes (même principe que le correctif déjà appliqué au tableau de synthèse A1-A10) ;
- **Collecte OWASP** : Une collecte réelle sur un projet avec des messages SonarQube longs (notamment certaines règles FindSecBugs) faisait échouer tout l'enregistrement du détail des vulnérabilités (« valeur trop longue pour le type character varying(255) », transaction entière annulée) — le message et le chemin de fichier d'une issue n'étaient jamais bornés avant l'insertion, alors que les colonnes correspondantes sont limitées à 255 caractères. Tronqués par prudence avant l'enregistrement ;
- **Export PDF OWASP** : L'en-tête de colonne « Composant »/« Component » des deux tableaux de détail (vulnérabilités, hotspots) s'affichait dans une police minuscule et fine au lieu du style d'en-tête normal — la règle appliquant une police à chasse fixe réduite aux chemins de fichier des cellules ciblait par erreur l'en-tête lui-même (même classe CSS réutilisée sur les deux) ;
- **Page et export PDF OWASP** : La répartition par module (Frontend/Backend/Autre) affichée à côté d'un référentiel donné ne correspondait jamais à son propre total de vulnérabilités — trouvé en comparant le PDF du référentiel 2021 (83 vulnérabilités) à sa répartition par module, qui totalisait 541 (le total du référentiel 2017). La répartition était calculée une seule fois globalement (toute vulnérabilité classée sur au moins un référentiel), puis cette même valeur était reportée à l'identique sur les lignes 2017 et 2021 de la table `owasp` — ce défaut touchait aussi bien la page web que le PDF, les deux lisant la même donnée. Calculée désormais séparément pour chaque référentiel.

### Nettoyé (style inline, portée transverse à 24 fichiers)

- **Templates** : Suppression de 76 attributs `style="..."` figés sur 89 recensés dans `templates/` (Batch, ZAP, Statistique, Activity, Suivi, Dependency-Check, et les 10 gabarits d'export PDF `rapport/*`), remplacés par des classes CSS réutilisables — complète le même nettoyage déjà fait sur `owasp.css` lors du chantier ci-dessus. Les 13 occurrences restantes sont des styles réellement dynamiques (`--percent: {{ row.percent }}`, `height: {{ heightPx }}px;` pour les barres de graphique), une valeur par ligne qu'aucune classe statique ne peut remplacer. Nouvelles classes utilitaires génériques dans `common.css` (`display-none`, `width/height-100-percent`, `flex-direction-column`, `flex-1-1-auto`, `word-break-all`, `cursor-pointer`, `gap-05`, `font-size-075`, `font-weight-600`/`700`, etc.) ; classes propres à une page quand la valeur était spécifique (`dependency-check.css` : `dc-score-good`/`warn`/`bad` pour un score sécurité jusqu'ici en couleur conditionnelle inline, `min-height-320`/`280` ; `suivi.css` : `chart-container-360`) ; classes locales dans le `<style>` embarqué de chaque gabarit PDF autonome (Dompdf ne charge pas la feuille de style applicative) — `page-break-before`, répété tel quel 6 fois dans un seul fichier, en est le meilleur exemple.
- **Page Activity** : Le spinner de rafraîchissement (`$spinner.show()`/`.hide()` en jQuery) serait resté bloqué invisible après la conversion de son état initial en classe CSS — `.show()`/`.hide()` ne manipulent que le style inline, jamais une classe. `index-activity.js` bascule sur `.removeClass('display-none')`/`.addClass('display-none')`, seul endroit où le changement a nécessité de toucher du JS en plus du CSS/Twig.

### Corrigé (suite, vérification manuelle sur les projets de tests)

- **Page Projet** : Sur la tuile Accessibilité, la ligne « Anomalies » (total) collait au détail par langage juste en dessous — marge basse ajoutée, même convention déjà utilisée partout ailleurs sur la page (`margin-bottom-07`).
- **Page Projet** : La couleur de l'option survolée/sélectionnée dans le sélecteur de projet (select2) était trop proche de la couleur de fond, rendant l'option sélectionnée peu visible — passée de `#00445b` à `#ddd`.
- **Page Répartition par module** : Le bouton « Historique » pouvait rester bloqué sur le message générique « Une erreur générale […] a été rencontrée (Erreur 500). » pour le reste de la session, même après une Collecte + Analyse + Enregistrement réussis entre-temps — un verrou de session (`sessionStorage`) se posait sur **toute** réponse autre que 200/201, y compris le 404 normal « pas encore de données historisées » (un état attendu et récupérable, pas une panne). Une fois posé, le clic suivant ne rappelait plus jamais l'API et affichait ce message générique au lieu du vrai résultat. Seule une vraie erreur serveur verrouille désormais ; un rechargement de page n'est plus nécessaire pour s'en sortir.

### Corrigé (suite, badge de note vide sur le tableau de synthèse A1-A10)

- **Page OWASP** : Le badge de note pouvait s'afficher sans couleur ni lettre (`class="badge "`) dès qu'une catégorie comptait exactement 1 occurrence d'une seule sévérité (ex. une seule anomalie mineure) — l'initialisation par défaut (`c=[], n=[]`, des tableaux vides) ne retombait sur aucune valeur valide quand aucune des conditions de seuil (`> 1`, au lieu de `>= 1`) ne matchait. Corrigé sur les 40 occurrences concernées (A1-A10 × Mineur/Majeur/Critique/Bloquant) ; corrigé également, la catégorie A4 comparait ses propres compteurs à ceux de la catégorie A1 (copier-coller, oui c'est de ma faute).

### Modifié (palette des notes et des couleurs, portée transverse à 9 fichiers CSS)

- **Notes A-E** (badges `.note-a`..`.note-e` / `.note-vertX`/`.note-jaune`/`.note-orange`/`.note-rouge`, Quality Gate `.qg-warn`) : La note D passe de `#ba5705` (brun, contraste 4,67:1 avec du texte blanc — à la limite du seuil WCAG AA) à `#ed7d20` avec du texte noir (7,55:1). Même correctif appliqué aux notes B et C de l'ancienne convention (`common.css`), qui gardaient encore du texte blanc malgré un fond trop clair pour ce contraste (ratio ~2:1 et ~1,7:1) — la convention plus récente des tuiles (RCI/Empreinte Écologique/Accessibilité/OWASP) l'avait déjà corrigé, pas l'ancienne ;
- **Orange** : Deux teintes différentes et incohérentes coexistaient pour un même usage (texte sur fond clair) — `#c75e08` (`.couleur-orange`) et `#ba5705` (`.color-orange`) — unifiées sur `#ba5705`, la seule des deux qui passe le contraste WCAG AA (4,67:1 contre 4,17:1) ;
- **Page Accueil** : `.couleur-vert`/`.couleur-rouge` étaient redéfinies dans la feuille de style de cette page avec des valeurs différentes de celles de `common.css` (`#971c09`/`#187e3d` contre `#bf0815`/`#048404`), les deux en `!important` — ces 2 classes rendaient différemment selon que la page chargeait aussi cette feuille de style. Découplées des classes `.plus`/`.moins`, qui gardent désormais leurs propres couleurs ;
- **Visibilité au survol sur fond bleu** : Le lien du pied de page et l'accordéon des préférences changeaient de couleur au survol vers `#ba5705`, une teinte trop proche en luminosité du bleu maison (`#00445b`) pour être clairement perceptible (écart de contraste 2,27:1) — passés à `#ed7d20` (écart porté à 3,81:1) ;
- **Mes Projets** : Les badges Fiabilité/Sécurité/Maintenabilité (`span.label.success/.warning/.alert`) affichaient les couleurs par défaut de Foundation (vert vif, orange ambré, rouge brique), jamais alignées sur la palette du reste de l'application — recolorés sur `#077e07`/`#ed7d20`/`#d4333f`. Largeur désormais fixe par colonne plutôt que dépendante du nombre de chiffres affichés (ex. `47` et `104 337` prenaient des largeurs très différentes dans la même colonne) — Fiabilité/Sécurité restent étroites (violations corrigées en priorité, rarement plus de 10 000), Maintenabilité est plus large (10 000 à 100 000 courant).

### Ajouté (suite)

- **Page OWASP** : Les 2 tuiles compteur (Vulnérabilités OWASP, Hotspot) affichent désormais une bordure gauche colorée selon leur note A-E (même palette que les badges), sur le même principe que les tuiles de la page Code-Clean — posée dynamiquement en JavaScript, la note n'étant connue qu'après l'appel de peinture.

### Corrigé (suite, audit des `!important` sur les couleurs — relevé par Sonar)

- **6 `!important` retirés**, sans changement visuel : `.plus`/`.moins` (page Accueil) et le hover `#ffc` de `.source-link`/`.timeline a` (aucune autre règle à surcharger) ; le hover du bouton OWASP désactivé (le sélecteur était déjà plus spécifique que son voisin, ID + classe + `:hover` contre ID + `:hover` seul) ; `.section-sep th` de `statistique-projet.css` (classe non utilisée actuellement, qualifiée par précaution avec `.tbl-projets` pour rester correcte si elle est réutilisée un jour).
- **4 classes passées en sélecteur doublé** (`.couleur-vert.couleur-vert`, technique CSS standard — même résultat, aucun changement de markup) plutôt que `!important`, dans `common.css` et son duplicata `easy-admin.css` : ces classes sont posées en JS par-dessus d'autres règles de même spécificité (1 classe) chargées après elles selon la page, le gain de spécificité règle le problème sans dépendre de l'ordre de chargement.
- **1 `!important` conservé volontairement** (`.clicked-true`/`.clicked-false` de `common.css`), documenté en commentaire : posées en JS sur des boutons identifiés par **ID**, qui ont déjà leur propre `background-color` — un ID bat toujours une classe, `!important` est ici la seule façon de surcharger.

### Modifié (suite, normalisation de la page Statistiques › Projet)

- **Page Statistiques projet** : Les badges de note A-E (`.note-A`..`.note-E`/`.note-na`), de Quality Gate (`.qg-ok`/`.qg-ko`/`.qg-na`) et le texte des violations dépassant un seuil (`.num-warn`) utilisaient eux aussi les couleurs par défaut de Foundation, une 3ᵉ palette incohérente avec les 2 autres déjà normalisées sur cette page et sur Mes Projets — alignés sur `#077e07`/`#a3c50b`/`#edbf05`/`#ed7d20`/`#d4333f` (pairage du texte noir/blanc corrigé au passage sur la note D, qui restait en blanc sur un fond devenu trop clair), `#e0e0e0`/`#5f5e5e` pour l'état « non disponible » et `#bf0815` pour `.num-warn`. Pas d'équivalent orange pour la Quality Gate : SonarQube n'a que 3 états réels (OK/ERROR/absent), pas de palier intermédiaire.

## [2.4.0] - 27-10-2026

### Ajouté

- **Page ZAP** : Une clé Maven peut recouvrir plusieurs cibles DAST réelles (ex. tier présentation + tier métier d'une même application, suivis sous la même clé côté SonarQube mais scannés séparément) — la page affiche désormais une « lane » (KPI, graphiques, tableaux et historique entièrement indépendants) par cible (`target_url`) distincte détectée pour le projet, plutôt que de mélanger leurs scans dans un historique unique. L'export PDF accepte un paramètre `target_url` pour cibler la lane à exporter (repli sur la cible la plus récemment scannée si omis, pour les liens déjà existants) ;
- **Page OWASP** : La modale « Détail » d'une catégorie (icône 🔎 du tableau de synthèse) affiche désormais la répartition par sévérité des **vulnérabilités** (Bloquant/Critique/Majeur/Mineur), en plus de celle des hotspots (Élevée/Moyenne/Faible) déjà présente — elle ne montrait jusqu'ici jamais les vulnérabilités, même quand la catégorie en comptait ;
- **Personnalisation du nom affiché** : Le nom de l'application affiché dans l'interface (titre des pages, en-tête, pied de page, mentions légales, rapports PDF…) est désormais piloté par la nouvelle variable d'environnement `MA_MOULINETTE_NAME` (défaut : `Ma-Moulinette`), exposée à tous les templates Twig via une variable globale `app_name` — plus besoin de modifier le code pour déployer l'application sous un autre nom. Ne concerne pas le nom de domaine `ma-moulinette.fr` ni le dépôt GitHub, cités tels quels. Voir « Pour bien démarrer » pour la marche à suivre ;
- **Page Projet** : Nouveau bloc « Accessibilité », basé sur le tag SonarQube `accessibility` (122 règles CSS/Web/JavaScript/TypeScript, pas de facette WCAG officielle côté SonarQube) : nombre d'anomalies et note A-E globale, détaillées directement par langage (4 lignes, sans modale). Suit le pattern RCI (pas celui, défaillant, de l'Empreinte Écologique) : l'historique est alimenté par les deux pipelines, y compris un enregistrement manuel depuis la page. Disponible dans les deux présentations (classique et tuiles).

### Corrigé

- **Collecte** : SonarQube renvoie parfois une clé `<null>` dans `ncloc_language_distribution` quand il ne détermine pas le langage d'une partie du code (ex. `<null>=71;css=79;java=1948;...` — sa propre interface affiche « UNKNOWN » pour ce même cas) — la clé brute `<null>` remontait telle quelle jusqu'à l'affichage. Normalisée en « Inconnu » une seule fois, à la collecte (`BuildMapHistoryService`, point d'entrée partagé par la collecte interactive, le batch/cron et la commande de reconstruction de l'historique), pour ne plus avoir à la retraiter à chaque peinture ;
- **Page ZAP** : Le scan « le plus récent » affiché en tête de page pouvait être choisi de façon non déterministe quand deux scans partageaient exactement le même horodatage (`generated_at`) — cas réel après ingestion d'un rapport ZAP multi-site scindé en plusieurs rapports mono-site (même horodatage hérité du rapport d'origine). Un critère de tri secondaire (`id` décroissant) départage désormais les égalités, en faveur du scan le plus récemment ingéré ;
- **Page ZAP** : Ajustements de la modale de détail d'une alerte (taille agrandie, marge basse manquante sur le pied de modale) et d'un identifiant de bouton de fermeture manquant dans la feuille de style ;
- **Page ZAP** : Depuis l'introduction des lanes, les modales de détail d'une alerte ne s'affichaient plus pour aucune alerte, quelle que soit la page (boucle du template sur une variable qui n'existait plus au niveau racine) — corrigé en itérant sur les alertes de chaque lane ;
- **Page OWASP** : Le bouton d'accès à la page ZAP ne tenait plus compte que les scans sont désormais répartis en plusieurs cibles (lane) pour un même projet — corrigé pour afficher le bouton dès qu'au moins une cible a un scan, la page ZAP se chargeant elle-même de les restituer toutes ;
- **Export PDF ZAP** : L'aperçu tronqué de la description d'une alerte affichait les balises `<p>` du HTML d'origine de façon littérale (texte échappé, pas interprété) — nettoyé avant troncature ;
- **Page Projet** : Le journal de collecte affichait le même message (« J'ai trouvé X failles. ») pour les référentiels OWASP 2017 et 2021, sans distinction — impossible de savoir lequel des deux comptes concernait quel référentiel. Chaque message précise désormais le référentiel ;
- **Page OWASP** : Au clic sur une barre du graphique « Répartition des failles », l'infobulle affichait `[object Object]%` au lieu du nombre réel — `context.parsed` est l'objet `{x, y}` du point sous Chart.js, pas directement la valeur ; corrigé pour lire `context.parsed.y` ;
- **Page Projet** : Lancer une collecte puis cliquer directement sur « Enregistrer » sans passer par « Afficher » faisait planter `enregistrement.js` (`TypeError: Cannot read properties of null (reading 'dataset')`, sans message pour l'utilisateur) — un élément du DOM posé uniquement par `peinture.js` était lu sans garde-fou. Message clair désormais affiché (« Aucune donnée à enregistrer : lance une Peinture avant d'enregistrer. ») ;
- **Page OWASP** : Trois bugs réels dans le calcul des vulnérabilités par catégorie, trouvés en recoupant les résultats réels d'un projet avec des appels API SonarQube directs — secours par tag `owasp-aXX` zéro-paddé ne matchant jamais le vrai format des tags (`owasp-a6`, pas `owasp-a06`), bascule tout-ou-rien par référentiel masquant les catégories classées uniquement par tag dès qu'une autre catégorie du même référentiel était classée par la facette officielle, répartition par sévérité structurellement vide pour toute catégorie classée par la facette officielle — voir la documentation « Page OWASP » pour le détail complet des 3 bugs ;
- **Page OWASP** : Le comptage par sévérité de la modale « Détail » d'une catégorie (hotspots) ne filtrait pas par référentiel — il sommait silencieusement 2017 et 2021, affichant le même résultat quel que soit l'onglet actif. La modale affichait aussi le contenu de la catégorie précédemment cliqué pendant un instant avant de se mettre à jour (ouverture avant la fin du chargement asynchrone) ;
- **Page OWASP** : La modale « Détail » d'une catégorie était trop étroite pour aligner ses 4 colonnes de sévérité des vulnérabilités sur une seule ligne (la 4ᵉ passait à la ligne, incohérent avec la ligne de hotspots juste en dessous) et son titre n'utilisait pas la police/le style du reste de la page — largeur et titre alignés sur la convention du reste de l'application ;
- **Page Projet (Accessibilité)** : Un langage absent du projet (aucune ligne dans `ncloc_language_distribution`, ex. TypeScript sur un projet qui n'en a pas) affichait `0` anomalie et aucune note — indiscernable d'un langage réellement présent et sans aucune anomalie trouvée. `0` reste désormais réservé à ce dernier cas ; un langage absent affiche un tiret grisé (nouvelle classe `.note-gris`) et son appel API `/api/issues/search` n'est plus effectué du tout ;
- **Page Projet (tuiles)** : Sur la tuile « Répartition », la ligne « Maintenabilité » (deux compteurs non compressibles, mauvaises pratiques réelles / total) débordait de la tuile dès qu'un projet comptait beaucoup de violations. Scindée en deux sous-lignes (« Réelles » / « Total », même convention que le détail par langage d'Accessibilité), sans changer la largeur des tuiles ;
- **Page Projet** : `clean_screen()` (effacement de l'écran avant une nouvelle collecte) ne nettoyait pas les champs Accessibilité (compteur et note globaux, détail par langage, attribut `data-accessibility-mesures-json` posé par la peinture) — les anciennes valeurs restaient affichées pendant le chargement d'une nouvelle collecte. Corrigé, sur le même modèle que le bloc Empreinte Écologique voisin ;
- **Migrations** : La table `accessibility` n'avait ses index et commentaires de colonnes que dans le script idempotent (`updates/13_create_accessibility_table.sql`), pas dans les fichiers centralisés utilisés par une installation complète (`40_indexes/indexes.sql`, `60_comments/comments.sql`) — une base créée via `99_master_install.sql` se serait retrouvée sans ces index/commentaires, contrairement à une base mise à jour via le script idempotent. Complété, aligné sur `eco_conception` ;
- **Historique** : L'entité Doctrine `Historique` n'avait jamais été mise à jour avec les 15 colonnes `accessibility_*` — contrairement à RCI et à l'Empreinte Écologique, dont les colonnes équivalentes sont mappées depuis leur introduction. La colonne SQL et la whitelist d'insertion dynamique (`HistoriqueRepository::$historiqueColumns`) étaient correctes, seule l'entité elle-même ignorait ces colonnes (aucun getter/setter, pas de contrainte de validation sur les notes). Complété, aligné sur le bloc Empreinte Écologique voisin.

### Sécurité

- **Page ZAP** : La description, la solution et la référence de chaque règle ZAP (HTML fourni par le catalogue de règles de ZAP) étaient rendues sans aucune neutralisation côté Symfony (`|raw`), malgré un commentaire de sécurité dans le code indiquant l'inverse — un rapport déposé via `POST /api/secure/zap/upload` (protégé par jeton, mais pas par validation de contenu) aurait pu faire exécuter du JavaScript dans le navigateur d'un utilisateur consultant la page. Ces 3 champs sont désormais neutralisés une seule fois, à l'ingestion, via [symfony/html-sanitizer](https://symfony.com/doc/current/html_sanitizer.html) (voir la documentation « Gestion de la sécurité »), avant toute persistance en base.

### Documentation

- **Architecture base de données** : La page décrivant le modèle de données n'avait pas suivi l'ajout des tables ZAP (DAST) (`zap_processing_queue`, `zap_scan`, `zap_rule`, `zap_finding`, `owasp_dast`) ni de `eco_conception` (Empreinte Écologique, Creedengo) — nouveau diagramme entité-relation pour le domaine ZAP, nouvelle section dédiée, et l'ensemble des compteurs (tables, index, commentaires, contraintes, fonctions) recomptés et mis à jour ;
- **Page ZAP** : Nouvelle section décrivant les lanes (plusieurs cibles sous une même clé Maven) et le paramètre `target_url` de l'export PDF ;
- **Sécurité** : Nouvelle section décrivant la neutralisation du HTML externe des règles ZAP ;
- **Page OWASP** : Nouvelles notes décrivant les 3 bugs du calcul par catégorie (secours par tag, fusion catégorie par catégorie, répartition par sévérité) et les bugs de la modale « Détail » (filtre référentiel manquant, contenu pas à jour, absence de sévérité des vulnérabilités) ;
- **Page Projet** : Nouvelle note décrivant le crash d'enregistrement sans Peinture préalable et son correctif ;
- **Pour bien démarrer** / **Architecture technique** : Nouvelle section décrivant la variable `MA_MOULINETTE_NAME` et sa distinction avec `MARQUE_ENTREPRISE_SHORT`/`MARQUE_ENTREPRISE_LONG` (auteur/éditeur, pas le nom du produit) ;
- **Page Projet** : Nouvelle section « Bloc Accessibilité » décrivant le choix du tag `accessibility` (plutôt que `wcag`), l'absence de détail par critère WCAG exploitable via l'API, le pattern d'historique (aligné sur RCI, pas sur l'Empreinte Écologique) et la distinction « 0 mesuré » / « non calculable » ;
- **README** / **Tests unitaires et d'intégration** : Statistiques (lignes de code, nombre de tests/assertions, couverture) régénérées via `app:admin:refresh-stats` et `composer test:coverage` (4 220 tests / 11 380 assertions, 80,95 % de lignes couvertes), version affichée alignée sur 2.4.0 ;
- **Tests** : `Entity\Accessibility` et `Repository\AccessibilityRepository` n'apparaissaient même pas dans le rapport de couverture (0 % réel, comme l'entité `EcoConception`, un gap déjà existant dans le projet) — 48 nouveaux tests ajoutés (`AccessibilityCaseTest` : 18 paires getter/setter, `AccessibilityValidationTest` : contraintes `Assert` NotBlank/Length/PositiveOrZero/Choice exercées avec le vrai validateur Symfony, `AccessibilityRepositoryTest` : `handleDatabaseException` et les branches `catch`/`rollback` des 3 méthodes SQL). `Entity\Accessibility` passe à 98,27 % de lignes couvertes, `AccessibilityRepository` à 100 % ;
- **Tests** : `Entity\Historique` n'avait jamais été mise à jour avec les 15 colonnes `accessibility_*`, contrairement à ses équivalentes `rci_*`/`eco_*` — 25 tests supplémentaires (`HistoriqueCaseTest`, `HistoriqueValidatorTest`) couvrent désormais les getters/setters et les contraintes de validation des 5 notes A-E, et `HistoriqueRepositoryTest` (Integration) prouve que ces colonnes sont réellement persistées par l'INSERT dynamique de `HistoriqueRepository`, pas silencieusement ignorées par sa whitelist statique. Une fixture `AccessibilityFixtures` (nouvelle, calquée sur `RciFixtures`) corrige par ailleurs une incohérence : Accessibilité disait suivre le pattern RCI partout, sauf pour son test Integration, qui construisait ses données sans fixture.

## [2.3.0] - 25-10-2026

### Ajouté

- **Préférences** : Nouvelle option « Presentation » sur `/preferences` (même mécanisme de bascule Oui/Non que les favoris) permettant de choisir, pour la page Projet, entre la présentation classique (3 colonnes) et une nouvelle présentation en tuiles (une tuile par domaine : Application, Volumétrie, Complexité, Qualité interne, Tests & Violations, Analyse, Empreinte, Qualité, Répartition, Dette). Désactivée par défaut (présentation classique) pour ne rien changer aux utilisateurs existants ;
- **Mes Projets** : Ajout d'un encart d'information précisant que la colonne Version correspond à la version analysée lors de la dernière collecte, pas nécessairement à la dernière version publiée du projet ;
- **Page Projet** : Affichage de métriques déjà collectées et persistées par SonarQube, mais jamais montrées jusqu'ici, dans les deux présentations (tuile et classique) — statut du cycle de vie des violations (ouvertes/confirmées/réouvertes) et violations acceptées à fort impact dans le bloc Tests & Violations ; détail de la couverture (lignes/branches/conditions non couvertes) et de la duplication (lignes/blocs/fichiers dupliqués), avec une barre de progression pour chacune des deux mesures globales, dans le bloc Qualité interne ; nombre total de points de complexité cyclomatique et cognitive (nouvelle section « Points de complexité »), en plus des ratios déjà affichés, dans le bloc Complexité.

### Corrigé

- **Mes Projets** : Les en-têtes de colonne du tableau (DataTables) restaient alignés à gauche malgré la classe `text-center` ajoutée dans le template — la feuille de style du thème DataTables/Foundation (`table.dataTable thead th { text-align: left; }`) est plus spécifique que la classe utilitaire Foundation `.text-center` et la surcharge systématiquement, y compris sur la ligne d'en-tête groupée (colspan) que les réglages `columnDefs`/`className` de DataTables ne peuvent pas atteindre. Corrigé en ciblant l'ID de la table (`#table-mes-projets thead th.text-center`), pour les deux lignes d'en-tête ;
- **Page Projet (tuiles)** : Dans les nouveaux blocs de présentation en tuiles, chaque titre de section avait une balise `<span>` d'icône mal fermée (`<span aria-hidden="true">🏀<span>Titre</h2>`), ainsi qu'un attribut `title` mal orthographié (`titre=`) et une coquille visible (« NoS0NAR » au lieu de « NoSONAR ») — ces deux dernières également présentes dans le bloc classique correspondant, corrigées aux deux endroits ;
- **Page Projet (tuiles)** : Les tuiles utilisaient une largeur calée sur leur contenu (`large-auto`), ce qui donnait des tuiles de largeurs très inégales sur une même ligne au sein d'un même bloc — remplacées par des largeurs fixes par breakpoint. L'espacement entre tuiles est désormais géré par `grid-margin-x` (compense proprement la gouttière à tous les breakpoints, y compris en mode empilé sur mobile, contrairement à une marge manuelle sur les cellules) ;
- **Page Projet (tuiles)** : Les icônes de section (emoji) étaient répétées sans distinction entre plusieurs tuiles d'un même bloc (⚾/🏀/👜 réutilisés partout) — remplacées par des icônes distinctes et porteuses de sens ; la tuile « Répartition » dupliquée (deux tuiles portant le même nom pour des données différentes : violations/scope d'un côté, répartition par module applicatif de l'autre) a été renommée en « Modules » ;
- **Page Projet** : La ligne « Dette technique » de la tuile/bloc Dette pouvait chevaucher la ligne suivante (« Ratio de dette ») quand la durée affichée était longue, faute de place sur une ligne partagée avec le label et le bouton de détail — scindée en deux lignes (label + bouton, puis la durée sur sa propre ligne) ;
- **Page Projet** : En largeur mobile, la marge fixe de 2rem de part et d'autre du contenu de la page laissait trop peu de place aux tuiles, provoquant des débordements visuels — réduite (0,2 à 0,5rem selon la largeur) sur ce breakpoint uniquement ;
- **Page CoSUI** : Le graphique de distribution des notes touchait le pied de page, faute de marge sous le dernier bloc de contenu — ajout de la marge standard déjà appliquée ailleurs dans l'application ;
- **Sécurité HTTP** : Les tests `SecurityHeadersSubscriberTest` (Unit et Integration) attendaient encore un en-tête `Content-Security-Policy` en environnement de débogage, alors que le correctif CSP du 21/10 (désactivation volontaire du CSP quand `kernel.debug` est vrai, pour ne plus bloquer les scripts injectés par la Web Debug Toolbar) a changé ce comportement — tests mis à jour pour refléter le nouveau comportement voulu (aucun CSP en dev, CSP complet en prod) ;
- **Page Accueil** : Le batch d'analyse User-Agent (`runBatch()`) est désormais aussi déclenché depuis la page Accueil (en plus de Projet et Statistique), avec une taille de lot codée en dur (`100`) au lieu du paramètre configurable `USER_AGENT_BATCH_AUTOMATIQUE` déjà utilisé par les deux autres pages — corrigé pour respecter ce réglage, y compris la désactivation du batch quand la variable d'environnement est vide ;
- **User-Agent** : Deux appels concurrents à `runBatch()` (déclenchables depuis Accueil, Projet et Statistique) pouvaient sélectionner et traiter le même événement en double, faute de verrouillage lors de la réclamation du lot — nouvelle méthode `UserAgentEventRepository::claimPendingEvents()` (`SELECT ... FOR UPDATE SKIP LOCKED` dans une transaction courte), qui remplace l'ancien verrouillage ligne par ligne non protégé contre la concurrence. Même principe déjà utilisé par `DcProcessingQueueRepository`/`ZapProcessingQueueRepository::claimNextBatch()`.

## [2.2.0] - 18-10-2026

Ajout de nouvelles fonctionnalités à l'application.

### Ajouté

- **USER_AGENT** : Personnalisation du nombre de `USER_AGENT` traité par le batch automatique (page PROJET) et manuel (index). Attention, deux nouvelles propriétés doivent être ajoutées.
- **Tests** : Ajout des tests unitaires pour couvrir les paramètres du nombre de tracements à réaliser en manuel ou automatique ;
- **RCI** : Intégration de l'indicateur RCI (plugin `sonar-rci`) aux indicateurs standards, avec un détail par module (frontend/backend/autre/inconnu) en plus de la mesure globale — nouvelle table `rci`, colonnes `historique.rci_*` (indicateur, note, nombre de lignes de code et poids des anomalies, pour le global et chaque module), calculé automatiquement à la suite de la collecte (aucune action manuelle requise). Côté page Projet : note A-E affichée à côté de la Quality Gate, et nouvelle fenêtre de détail (tableau + graphique en barres empilées RCI/dette) par module.
- **Anomalies** : Répartition des anomalies par portée SonarQube (`MAIN` = code source, `TEST` = code de test), globale et par catégorie (bug/vulnérabilité/mauvaise pratique) — 8 nouvelles colonnes sur la table `anomalie` et sur `historique`, calculées par les deux flux de collecte (batch/cron et interactif). Côté page Projet : ligne « Scope Main/Test » (répartition en %) et nouvelle fenêtre de détail (tableau + graphique en barres empilées) par catégorie, sur le même modèle que RCI.
- **Historique** : Chaque ligne de `historique` porte désormais la version du serveur SonarQube qui l'a produite (colonne `sonarqube_server_version`, simple marqueur de traçabilité, « inconnu » si la requête échoue — aucun affichage associé) ;
- **Suivi** : Tendance RCI et Scope Main/Test ajoutée à la page Suivi (tableau de synthèse + nouveau graphique en lignes pleines) et au rapport PDF (tableau de synthèse + nouvelle page dédiée RCI : note, indice global, indice ncloc, indice de dette, par version) ;
**Empreinte Écologique** : Nouvel indicateur basé sur les plugins SonarQube Creedengo (creedengo-java/javascript/php/python, détection d'anti-patterns éco-conception) — nombre d'anomalies, dette estimée et note A-E (seuils SQALE 5/10/20/50%, identiques au RCI), globaux et par langage (Java/JavaScript/PHP/Python), calculés automatiquement à la suite de la collecte (aucune action manuelle requise), nouvelle table `eco_conception` + colonnes `historique.eco_*`. Activable via la propriété applicative `CREEDENGO_ENABLED` (même principe que l'extension `track-logger-method`) : désactivé par défaut, les serveurs SonarQube sans ces plugins ne sont pas impactés et la page Projet affiche `-`. Côté page Projet : ligne « Empreinte Écologique » (nombre d'anomalies + badge de note).
- **Mesures** : Collecte des 3 métriques `maintainability_issue_severity`, `reliability_issue_severity`, `security_issue_severity` (apparues sur SonarQube entre les versions 26.3.0 et 26.9.0, pas encore documentées par SonarSource au moment de l'ajout) — nouvelles colonnes nullables sur `mesures` et `historique`, valeur brute (pas de note A-E, sémantique pas encore connue). Récupérées via un appel `/api/measures/component` séparé et non bloquant : un serveur qui ne les connaît pas encore renvoie simplement `null` sur ces 3 colonnes, sans impacter le reste de la collecte des mesures. Collecte uniquement pour l'instant (pas d'affichage sur la page Projet) ;
- **ZAP (DAST)** : Nouvel indicateur complémentaire au module OWASP existant (analyse statique SonarQube) : ingestion de rapports produits par le scanner dynamique [ZAP](https://www.zaproxy.org/), postés par la CI via `POST /api/secure/zap/upload` (token bearer dédié, file d'attente, worker `app:zap:process`, découplé des pipelines de collecte habituels — même principe que l'extension OWASP Dependency-Check déjà en place). Les alertes ZAP sont automatiquement regroupées par catégorie OWASP Top 10 (2017 et 2021, les deux référentiels déjà utilisés côté analyse statique), nouvelle table `owasp_dast`. Nouvelle page `/zap`, accessible comme Dependency-Check depuis la page OWASP (rôle `ROLE_SECURITY`) : indicateurs clés, répartition par catégorie et par niveau de risque, graphiques (dont l'évolution dans le temps) et historique des analyses. Nouvelle commande `app:zap:purge` (purge des rapports terminés plus anciens que N jours, même principe que `app:dependency-check:purge`) ;
- **Commandes Prod** : Journalisation dédiée des commandes planifiées par le cron Docker (`app:*`) sur un nouveau channel Monolog `cron` (fichier `var/log/cron.log` en production), plutôt que sur le journal applicatif générique — consultable depuis la page d'administration des journaux (nouveau filtre « Cron »). S'applique à 8 des 10 commandes de `src/Command/Prod/` (les 2 exceptions : `app:sonar:compare-metrics`, outil de diagnostic sans journalisation, et `app:dependency-check:reprocess`, outil de reprise manuelle jamais planifié) ;
- **RGPD** : Purge automatique du journal des modifications de rôle (`app:user-role-log:purge`), de l'activité de collecte SonarQube (`app:activity:purge`, tables `activity`/`activity_historique`) et des événements/analyses User-Agent (`app:user-agent:purge`, tables `user_agent_event`/`user_agent_analysis`), jusqu'ici conservés sans limite — comble l'écart identifié dans [l'analyse de conformité RGPD](mkDocs/docs/ma-moulinette/conformite/rgpd.md). Durées de rétention par défaut (365 jours pour les deux premiers, 180 jours pour la donnée purement statistique User-Agent) réglables sans redéploiement via les variables d'environnement `USER_ROLE_LOG_RETENTION_DAYS`/`ACTIVITY_RETENTION_DAYS`/`USER_AGENT_RETENTION_DAYS`, cron quotidien ;

### Modifié

- **Historique** : Pour une version RELEASE, RC, BETA ou ALPHA, enregistrer une nouvelle analyse de la **même** version remplace désormais la ligne existante au lieu d'en créer une nouvelle (une seule ligne par version, la plus récente). Les versions SNAPSHOT ne sont pas concernées : leur numéro de version ne change pas entre deux analyses (plusieurs par jour en développement), chaque analyse reste donc historisée comme avant ;
- **Code-Clean** : Suppression du style inline sur la page (dashboard) ;
- **Statistiques** : Ajustement de la taille du titre (index) ;
- **Tests** : Utilisation de la fixture dc-json 2048-1.0-SNAPSHOT dans les deux tests "skipped" par faute de trouver un rapport dans /var ;
- **Doc** : Mise à jour des statistiques de tests (README, page d'accueil mkDocs, test-unitaire.md) ;
- **RCI** : Le calcul global et le détail par module partagent désormais une seule taxonomie de sévérité (sélectionnée selon la version de SonarQube) au lieu de deux paires de colonnes distinctes (`rci_core_*`/`rci_2024_*` fusionnées en `rci_indicator`/`rci_rating`) ;
- **PHPStan** : Campagne de réduction de la dette niveau 6 (153 → 68 notifications) — ajout des types manquants (propriétés, paramètres, retours, generics `ServiceEntityRepository`), typage explicite des doublures PHPUnit (`&MockObject`/`&Stub`) sur les propriétés de test, sans changement de comportement ;
- **Tests** : Couverture de lignes portée de 79,54% à 80,29% (objectif SonarQube) — complète les tests getter/setter des entités `Historique`, `Mesures`, `Anomalie`, `Owasp`, `BatchExecutionJournal` (attributs ajoutés aux sessions précédentes jamais exercés par un test) et crée les tests manquants pour `Rci`/`CleanCode` (aucun test unitaire n'existait pour ces deux entités, seuls les tests d'intégration les touchaient indirectement) ;
- **Appels SonarQube** : Le paramètre `componentKeys` de `/api/issues/search` (renommé `components` depuis SonarQube 10.2) est désormais choisi dynamiquement selon la vraie version du serveur interrogé (`GetSonarVersionService`, mise en cache 5 minutes), au lieu d'être codé en dur — `components` par défaut, `componentKeys` uniquement sur confirmation d'un serveur 8/9. Aucun changement de comportement sur les serveurs actuellement supportés (l'ancienne clé reste acceptée partout, seul un avertissement de dépréciation disparaît des journaux SonarQube).
- **Anomalies actives** : Le filtre de statut sur `/api/issues/search` (`statuses`, déprécié depuis SonarQube 10.4) est lui aussi choisi dynamiquement selon la vraie version du serveur — `statuses=OPEN,CONFIRMED,REOPENED` pour les serveurs 8/9/10, `issueStatuses=OPEN,CONFIRMED,ACCEPTED` à partir de 2024 (modèle de statuts redéfini par SonarQube, pas un simple renommage). Au passage, les 6 contrôleurs concernés utilisent désormais le même périmètre d'anomalies « actives » (certains ne filtraient jusqu'ici que sur `OPEN` seul). Vérifié en conditions réelles (comparaison de totaux avant/après un changement de statut réel sur un projet) — voir `mkDocs/docs/ma-moulinette/application/projet.md` pour la règle complète et ses conséquences.

### Corrigé

- **Anomalies par module** : Un projet jamais collecté côté anomalies (frontend/backend/autre/inconnu, répartition par portée MAIN/TEST) affichait `0` comme s'il avait été collecté avec zéro anomalie par module, au lieu de `-` (pas encore collecté). Corrigé côté `ApiPeintureController` (`?? null` au lieu de `?? 0`) et côté JS (`peinture.js` route désormais ces 4 champs par le helper d'affichage commun ; les champs non collectés ne sont plus écrits dans un `data-*` attribut, pour qu'`enregistrement.js` les transmette comme réellement absents plutôt que comme la chaîne `"null"`, qui aurait fait échouer l'enregistrement en base) ;
- **RCI** : Le report des indicateurs RCI globaux (indicateur/note) vers `historique` était incomplet lors de l'enregistrement interactif (page Projet) — seul le détail par module était pris en compte, pas la mesure globale ;
- **RCI** : La lecture de la dernière mesure RCI ne gérait pas le cas d'un projet sans collecte RCI préalable (absence de ligne en base), ce qui pouvait remonter des valeurs incohérentes au lieu d'un simple « aucune donnée » ;
- **Répartition** : Sélection de la facette de sévérité par son nom plutôt que par sa position dans la réponse SonarQube, pour rester correcte quel que soit le nombre de facettes demandées ;
- **Anomalies** : Les colonnes `historique.repartition_frontend/backend/autre/inconnu` restaient toujours vides lors d'une collecte planifiée ou manuelle (seule la saisie interactive les renseignait) — la répartition par module n'était historisée que dans un seul des deux flux de collecte ;
- **Historique** : Le nom de colonne envisagé pour la version du serveur SonarQube (`version_sonar`) était déjà pris par `information_projet.version_sonar`, qui désigne un concept différent (nombre de versions connues) — renommé en `sonarqube_server_version` avant toute collecte réelle ;
- **Appels SonarQube** : Quand une facette demandée n'existe pas sur la version du serveur interrogé (ex. `owaspTop10-2021` sur un serveur en version 8, cf. `SONAR_VERSION`), le message technique brut de SonarQube est désormais remplacé par un message explicite pointant vers le paramètre à vérifier, au lieu d'être affiché tel quel ;
- **Rapport PDF Suivi** : Les pastilles de note A-E et Quality Gate (tableaux « Synthèse Qualité », « Suivi des anomalies », tuiles KPI de la page de garde) étaient invisibles dans le PDF — un reformatage antérieur avait répété la classe CSS (`note note-{{...}}`) sur plusieurs lignes, ce qui scinde la classe en plusieurs mots invalides en HTML (`note-` et la lettre séparément) au lieu d'un seul (`note-a`) ; le texte restait alors blanc sur fond blanc. Remis sur une seule ligne.

## [2.1.0] - 20-09-2026

Ajout de nouvelles fonctionnalités à l'application. Mise à jour des dépendances symfony et des statistiques du projet.

### Ajouté

- **Statistiques** : Ajout de l'analyse automatique des statistiques d'utilisation de l'application à l'ouverture de la page projet.
- **Markdown** : Ajout des règles d'exclusion légitimes (MD024).
- **SQL**: Ajout du script d'update automatique de la table ma_moulinette (mise à jour des version 2.0.0 vers 2.1.0).

### Modifié

- **Code-Clean** : Ajout d'une private static pour remplacer un string littéral dans la page `StatistiqueController` ;
- **Symfony** : Migration 8.0.15 en 8.1.7 ;
- **README** : Nouveau tableau sur l'état du projet ;

## [2.0.2] - 2026-09-19

Corrections mineures.

### Ajouté

**Avatar** : Ajout du focus lors de la sélection de l'avatar sur l’événement 'change-me'.

### Modifié

- **Code-Clean** : Correction des balises fermantes et manquantes dans les templates Twig (owasp, accueil, auth, profil, batch, activity, preference projet, plan-du-site, statistiques) ;

### Corrigé

- **Avatar** : Correction du contentType du script `details.js` qui bloquait l'appel Ajax sur le choix de l'avatar.

## [2.0.1] - 2026-09-13

Corrections mineures.

### Modifié

- **projet** : Correction du nettoyage des valeurs quand on relance une collecte sur un projet déjà collecté et affiché ;
- **code-clean** : Correction des balises fermantes dans les templates Twig (erreurs de parsing) ;
- **formatage** : Conversion de certains fichiers en UTF-8 BOM (Windows) vers UTF-8 (Linux) pour éviter les erreurs de parsing Twig, remplacement du formateur twig par html dans vscode et reformatage de tous les fichiers Twig ;

## [2.0.0] - 2026-09-06

Reprise complète du projet initial (Symfony 5.4 / SQLite) vers une architecture Symfony 8 / PostgreSQL 18. Périmètre couvert depuis `v1.6.0`.

### Ajouté

- **Framework** : intégration de `knplabs/knp-paginator-bundle` ;
- **Docker** : stack `docker-compose.yml` complète (base de données, PHP-FPM, cron, NGINX), crontab planifiant les jobs `OWASP DependencyCheck`, `clean-tags`, `collecte` et `activity` via un sidecar Supercronic ;
- **Base de données** : table `Actuator`, colonnes `tags`/`visibilité` sur `liste_projet`, colonnes `mode_collecte`/`utilisateur_collecte` sur la quasi-totalité des tables, tables `batch_traitement`/`batch_profiling` ;
- **Sécurité** : rôles étendus (`COLLECTE`, `BATCH`, `ACTUATOR`, `SUPER_ADMIN`, `INTERNAL`…) ; authentification **LDAP** (OpenLDAP, Microsoft AD) en complément du local, avec provisioning automatique ; honeypot anti-bot sur l'inscription ;
- **Back-office EasyAdmin** : CRUD dédiés groupes fonctionnels/utilisateur, portefeuilles de projets et batch ; nouvelle page **Journal des rôles** (consultation, archive CSV/PDF) ; nouvelle page **Logs applicatifs** ;
- **Traitement & Profiling** : nouveau module de file de traitement (déclenchement manuel/automatique de la collecte, files distinctes, identifiants Ulid, journal d'exécution) et nouvelle page de mesure des performances des traitements ;
- **Actuator** : nouveau module de collecte pour applications Java (déclaration de points d'accès, extraction des clés JSON à chaque analyse, pastille et modale sur la page Projet) ;
- **Pages applicatives** : refonte ou enrichissement de Accueil (vues Projet/Version/Vide), Profil (suivi des référentiels), Activité (nouvelle page), Projet (dette technique, LOGGER Java, répartition par langage, NoSonar), Suivi (tableau des mesures), OWASP (référentiels 2017/2021), Préférences (versions favorites multiples) ;
- **Authentification** : indicateur de qualité du mot de passe à l'inscription, changement de mot de passe forcé à la première connexion admin, option « voir mon mot de passe » ;
- **Accessibilité (RGAA)** : mise en conformité progressive de l'application (sémantique HTML, ARIA, sous-titres) ;
- **Tests** : suite **Playwright End-to-End** (22 suites, `tests/e2e/`) couvrant les parcours applicatifs et back-office critiques — voir [Tests End-to-End](mkDocs/docs/ma-moulinette/developpement/test-e2e.md) ; couverture unitaire/intégration portée à 80 % (`tests/Unit` + `tests/Integration`).

### Modifié

- **Framework** : migration PHP 8.5.5 NTS, Symfony 6.4 → 8.0, PostgreSQL 18 (depuis SQLite), Twig 3, Doctrine DBAL 4/ORM 3, EasyAdmin 4, PHPStan/PHPUnit ;
- **Documentation** : migration vers **mkDocs** (Material) ;
- **Transverse** : décommissionnement de Webpack au profit de **Symfony asset-mapper** ; réécriture des processus de collecte (suppression de la duplication batch/utilisateur) ; normalisation des messages (flash, JS, logs) ; centralisation du traitement des erreurs HTTP/SQL ;
- **Actuator** : collecte **best effort** (un point d'accès en échec n'interrompt plus la collecte du projet) ;
- **Analyse statique** : campagne de réduction PHPStan, dette ramenée de 4 820 à 150 erreurs résiduelles (niveau 6 retenu comme cible du projet).

### Supprimé

- Table `tags` (remplacée par les colonnes `tags`/`visibilité` de `liste_projet`) ;
- Bookmark de préférence en base de données (remplacé par `sessionStorage`) ;
- Suite `tests/Functional` (remplacée par la suite Playwright End-to-End) ;
- « Mode Test » applicatif (remplacé par une vraie base de données de test).

### Sécurité

- Durcissement des contrôles d'accès serveur sur les CRUD EasyAdmin (Utilisateur, Groupes, Portefeuille, Batch), jusque-là protégés par le seul pare-feu ;
- Filtrage par groupe fonctionnel sur les endpoints Suivi/COSUI (périmètre applicatif, pas seulement l'authentification) ;
- Limitation de débit (`symfony/rate-limiter`) sur l'endpoint public de healthcheck.

## v1.6.0 - 30/11/2022 - Release

- [CSS] Utilisation du référentiel des couleurs SonarQube pour afficher les notes. Pages concernées : [home], [projet], [owasp], [suivi] et [cosui].
- [Page_COSUI] Ajout d'une page de synthèse de type Comité de Suivi (i.e COSUI) ;
- [CSS] La variation des indicateurs utilise maintenant la class [down] pour marquer une amélioration c'est-à-dire une baisse des signalements, la flèche est verte et pointe vers le bas et [up] pour marquer une augmentation des défauts. La flèche pointe vers le haut sa couleur est rouge ;
- [Page_Projet] Ajout du bouton COSUI ;
- [CSS] Refactorisation des class js- et -enabled pour la page [PROJET] ;
- [Page_Projet] Renommage de la page anomalie.details.html.twig en details.html.twig ;
- [Page_Home] Ajout d'un raccourci pour ouvrir directement la page de suivi ;
- [Page_Suivi] Amélioration de l'information utilisateur de la fenêtre modale d'ajout d'une version ;
- [Page_Suivi] Refactorisation de la page pour prendre en compte le référentiel des couleurs sonar ;
- [Page_Home] Ajout des indicateurs de visibilités ;
- [Page_Suivi] Amélioration de la gestion des codes HTTP (404) ;
- [Page_Dashbord] Ajout du contrôle d'intégrité de la base de données ;
- Mise à jour symfony 6.1.8 ;

## v1.5.0 - 12/10/2022 - RELEASE

- [Page_Home] : Gestion des versions ;
  - On vérifie si la version de l'application est dans la table [ma_moulinette] ;
  - On affiche un message  à l'utilisateur si la version de la base est plus ancienne que la version de l'application ;
- [Docker] : Mise à jour de la configuration pour la stack docker ;
- [Symfony] Migration 5.4.4 vers 6.1.4 ;
- [PHP] Migration 8.1.10 ;
- [Securité] Ajout d'un cipher de type legacy pour les accès depuis un serveur NGINX ;
- [Configuration] Remplacement des chemins absolus par des chemins relatifs pour la BD ;
- [Sécurité] Filtrage des connexions par type d'HOST (domaine, IP, RegEx) ;
- [Authentification] Gestion des utilisateurs.
  - Ajout de la table [utilisateur] ;
  - Ajout d'un formulaire de connexion [login] ;
  - Ajout d'un formulaire d'inscription [register];
  - Ajout d'un formulaire de bienvenue [welcome], une fois l'inscription réalisée ;
  - Ajout de l'option **remember-me** ;
  - Ajout d'un firewall et des rôles [USER], [GESTIONNAIRE] et [PUBLIC_ACCESS] ;
  - Ajout des icônes **logout** et **dashboard** et **utilisateurs** dans le haut de page ;
- Ajout du module EasyAdmin :
  - Personnalisation des pages, du menu et des contrôleurs CRUD ;
  - Ajout des indicateurs et des statistiques globales sur la page Dashboard ;
  - Ajout de la page de gestion des comptes utilisateurs [ROLE_GESTIONNAIRE] ;
  - Ajout d'un lien vers la page [HOME] ;
- Activation et optimisation du cache opCahe (pour la prod uniquement);
- Refactoring complet de la documentation ;
- [Projet] Ajout de l'événement **Collecte** sur le bouton `C` du menu rapide ;
- [Erreurs] Personnalisation des erreurs HTTP (en production) ;
- [Utilisateur] Ajout du compte admin ;
- [Home] refactoring de la page :
  - Suppression du code JS et HTML pour les composants nécessitant un droits Admin dans SonarQube ;
  - Suppression des appels Asynchrone JS pour afficher le nombre de projet et de profil ;
  - Ajout de la table properties, contenant la nombre de projet, de profil en base et sur le serveur et leur date de modification ;
  - Ajout dans le contrôleur du processus de gestion des projets et des profils (anciennement en JS) ;
  - Amélioration de l'affichage des messages d'alerte. Suppression du statut pour des callout ;
  - Affichage du nombre de projet et de profil en plus/moins ;
  - Correction SonarQube ;
- [Profil] Correction de l'affichage du statut Actif lors du rechargement de la liste ;
- [Paramétrage] Choix de la fréquence de mise à jour. Par défaut 1 jour ;

## v1.4.0 - 06/07/2022 - RELEASE

- [Page Projet] : Modification du pictogramme (SVG) pour le bouton répartition ;
- Modification des appels de web-service Asynchrones (11). Utilisation de la méthode Promise + Callback (await) ;
- [Page Projet] : Modification de la fenêtre modale 'liste des projets'. Ajout des actions suivantes :
  - [V] Sélectionner un projet de la liste et déverrouiller tous les boutons ;
  - [S] Supprimer le projet de la liste des projets déjà analysés ;
  - [C] Lance la collecte des indicateurs SonarQube et le calcul des agrégats ;
  - [R] Lance la restitution des données calculés ;
  - [I] Ouvre la page de suivi des indicateurs ;
  - [O] Ouvre la page du rapport OWASP ;
  - [RM] Ouvre la page de suivi de la répartition par module ;
- [Page Projet] : Ajout de l'attribut "liste" de type boolean à la table "Anomalie" pour gérer l'affichage ou non, du projet, dans la liste des projets déjà analysés.

## v1.3.0 - 03/07/2022 - RELEASE

- [Page Suivi] : Correction de l'affichage du tableau de suivi des anomalies par type et sévérité ;
- [Page Projet] : Correction - le bouton analyse Owasp est bloqué tant que l'utilisateur n'a pas choisi un projet ;
- [Page Répartition] Correction - "Impossible to access a key ("setup") on a string variable ("NaN")." ;
- [Page Suivi] : Remplacement des valeurs "null" par -1 au lieu de 0 ;
- [Page Suivi] : Amélioration de l'affichage du tableau de suivi par type d'anomalies (i.e. mise en couleur des types) ;
- [Page Suivi] : Prise en compte de la version de référence pour le tableau de suivi des anomalies par type et sévérité ;
- [Page Répartition] : Ajout du tableau de bord par répartition : frontend, backend, autre ;
- [Page Répartition] : Ajout de l'Indice de Confiance ;
- [Page Répartition] : Masquage des tableaux vides (Fiabilité, sécurité et maintenabilité) ;
- [Page Répartition] : Ajout de la notion de set-up pour la gestion des versions collectées dans la table temp_repartition ;
- [Architecture] :Séparation des EntityProviders : **default** pour la base des données agrégées et **secondary** pour la base des données d'analyse ;
- [Architecture] : Reworking des requêtes pour la base temp. Suppression des requêtes avec EntityManager (SQL), ajout des requêtes en utilisant le ManagerRegistry (findBy,etc...) ;
- [Page Répartition] : Amélioration de l'affichage du tableau des sévérités par type de la page "Repartition". Utilisation d'un accordéon pour réduire la taille de la page ;

## v1.2.6 - 02/06/2022 - Release

- [Suivi des indicateurs] : Correction de la méthode de suppression d'une version dans la fenêtre modale "Modifier les paramètres" ;

```plaintext
Uncaught PHP Exception Doctrine\DBAL\Exception\SyntaxErrorException:
"An exception occurred while executing a query: SQLSTATE[HY000]:
General error: 1 near "4.1": syntax error"
```

- [Suivi des indicateurs] : Amélioration de l'affichage du graphique (mode responsive) ;
- [Suivi des indicateurs] : Correction des labels tronqués en haut du graphique ;
- [Suivi des indicateurs] : Modification de la méthode d'injection des données pour le graphique ;

## v1.2.5 - 24/05/2022 - Release

- Corrections de l'affichage de la note pour les vulnérabilités et du nombre de mauvaises pratiques, du tableau de la fenêtre modale "ajouter une analyse" (BoardController.php et app-dash.sj) ;
- Correction de l'enregistrement des données collectées lors de l'ajout d'une analyse historisée. Ajout des attributs obligatoires dans la requête (facultatifs en version 1.2.0).
- Formatage et nettoyage de toutes les requêtes complexes SQL : `trim(preg_replace("/\s+/u", " ", $sql))` ;
- Remplacement dans la page de suivi des indicateurs des valeurs null enregistrées avec la valeur "-1" par un tiret "-" ;
- Ajout du rechargement de la page de suivi des indicateurs quand on clique sur le bouton "fermer" de la fenêtre "ajouter une analyse" ;
- Correction du problème des valeurs "null" pour les hotsposts (valeurs non calculés sur les anciennes version de SonarQube) ;
- Correction de la date dans le tableau de suivi quand on ajoute une version depuis l'historique (date du jour au lieu de la date de la version) ;

## v1.2.4 - 17/05/2022 - Release

- Ajout de nouvelles exceptions sur les modules Backend et Autre ;
- Mise à de la documentation
- Correction du tri sur le tableau des hotspots (page OWASP / Détails).
- Ajout de l'attribut "niveau" à la table hotpspot_details pour gérer le tri ;
- Remplacement du mot "Batch" par "Autre" pour prendre en compte les modules de type "Batch" ou les Autres ;
- Modification des tables : Anomalie, Historique et HotspotDetails pour prendre en compte le changement de l'attribut "batch" par "autre" ;

## v1.2.3 - 12/05/2022 - Release

- Correction du traitement et de l'affichage de failles par module.

## v1.2.2 - 11/05/2022 - Release

- Ajout d'une base vierge pour démarrer ;
- ApiProjectController : Warning: Undefined array key "date_enregistrement" (ligne 1461) ;
- Correction du format de la date : date("m/d/Y) à date("d/m/Y) pour la page dash, profil et home ;
- Correction de l'ordre des colonnes pour le tableau de suivi des sévérités par type ;
- Correction des hotspots corrigés et toujours en base ;
- Correction "Warning: Undefined array key [line]", suppression de l'affectation en double ;
- Correction de la page OWASP (erreurs typographiques lors de refactoring) ;

## v1.2.1 - 08/05/2022 - Release

- Erreur : "Cannot instantiate interface Psr\Log\LoggerInterface". On n'utilise plus l'instanciation (new LoggerInterface) mais l'injection de dépendance ;
- Erreur de syntaxe dans l'utilisation de littérales dans le contrôleur ApiProjetContrôleur ;
- Suppression des dépendances inutiles dans HomeControleur et ProfilControleur ;
- Correction des fautes et erreur de typographie dans le fichier readme.md ;

## v1.2.0 - 05/05/2022 - Release

- Corrections SonarQube ;
- Ajustement du niveau de log (debug-->info) ;

## v1.1.0 - 24/04/2022 - Release

- Mise à jour du modèle de base de données ;
- Collecte des sévérités par type lors de la collecte, enregistrement dans la table historique, attention le modèle de la table est changé, il faut ajouter les nouveaux attributs (cf. readme);
- Suppression de la table temp_anomalie et réutilisation de la table anomalie, pour enregistrer le détail des sévérités par type ;
- Ajout dans la page du suivi des indicateurs, d'un tableau permettant d'afficher par version, le type d'anomalie par sévérité des trois dernières versions ;
- Correction du problème d'affichage des favoris et de la valeur de référence ;
- Ajout de la date de la version pour les tableaux de suivi ;

## v1.0.0 - 10/04/2022 - Release

- Ajout d'un exemple d'édition pour la page suivi des indicateurs ;
- Amélioration de la page et indication de la sévérité en rouge ou vert en fonction de la tendance.

## v0.0.11 - 07/04/2022

- Mise en place du socle de génération de rapport PDF ;
- Ajout dans le tableau de suivi des violations par sévérité mineure ;
- Correction de la couleur pour indiquer la version de référence (rouge) ;

## v0.0.10 - 02/04/2022

- Correction de l'affichage des notes lorsque l'on importe une version manuellement depuis la fenêtre d'ajout de la page de suivi ;
- Blocage des boutons quand le projet n'est pas choisi dans la page projet ;

## v0.0.9 - 31/03/2022

- Remplacement des favicons et du logo ;
- Correction de l'affichage de la version de référence et des favoris (problème de Boxing du Boolean initial et favori );
- Ajout d'un lien "modification" sur la page de suivi des mesures.
  - Permet de supprimer une version ;
  - Active ou désactive, pour une version, le statut favori ;
  - Active ou désactive, pour une version, le statut référence ;
- Reworking des favoris dans la page home ;
- Correction de l'affichage quand le nombre de violations est >20000 (20k) ;

## v0.0.8 - 29/03/2022

- Corrections et amélioration ;
  - Ajout de la note Z pour les applications n'ayant de hotposts ;
  - Correction typographique ;

## v0.0.7 - 28/03/2022

- Version de recette ;
- Mise à jour de la documentation ;
- Mise à jour de la procédure de démarrage et d'arrêt ;

## v0.0.6 - 18/03/2022

- Corrections Sonarqube ;
- Amélioration de la page de suivi des indicateurs (Dash) ;
  - Affichage de la version de référence ;
  - Affichage des 9 dernières versions ;
- Ajout d'un formulaire d'ajout d'une version à partir des données Sonarqube disponible dans l'historique ;
- Refactoring de la sauvegarde ;
- Refactoring de la méthode d'ajout manuel d'une version ;
- Corrections des bugs ;

## v0.0.5 - 13/03/2022

- Ajout de la page dash, pour afficher des tableaux de suivi ;
- Ajout du tableau de suivi des versions analysées ;
- Ajout de graphique pour suivre l'évolution des violations par type ;
- Ajout du tableau de suivi des violations par modules (webapp - front, api - back et batch) ;
- Ajout du tableau de suivi les violations par sévérité (bloquant, critique et majeur) ;

## v0.0.4 - 01/03/2022

- Refactoring de la page projet, création d'une page détails ;
- Mise en place d'un système de sauvegarde pour les données calculées ;
- Correction des bugs ;

## v0.0.3: 25/02/2022

- Correction de bug ;
- Tests de reprise de plusieurs gros projets à plus de 10000 défauts ;
- Migration de la page security en OWASP ;

## v0.0.2: 24/02/2022

### Fonctionnalités principales

- Création de la page **Home** :
  - Ajout du header/footer ;
  - Ajout du fil d’Ariane ;
  - Ajout d'une zone pour afficher les traces ;
  - Ajout d'un spinner (à revoir) ;
  - Ajout d'un bouton pour lancer la collecte des projets disponibles sur le serveur sonar ;
  - Affichage du nombre de projets disponible ;
  - Ajout d'un bouton pour afficher la liste des profils qualités ;
  - Affichage du nombre de profils disponible ;
  - Ajout d'une zone Favori, pour afficher les projets favoris (partielle) ;
  - Ajout du bouton "mon projet" pour accéder à la page des projets ;

- Création de la page **Projet** :
  - Ajout du header/footer ;
  - Ajout du fil d’Ariane ;
  - Ajout d'une zone pour afficher les traces ;
  - Ajout d'un spinner (à revoir) ;
  - Ajout d'un bouton pour lancer la collecte des données depuis le serveur Sonar ;
  - Ajout d'une liste de sélection avec recherche à partir de deux lettres ;
  - Ajout d'un petit-cœur, pour marquer les projets Favoris ;
  - Ajout d'un bouton permettant d'afficher l'ensemble des projets présents dans la base locale et ceux en favoris ;
  - Ajout d'un bouton permettant la génération et la restitution des indicateurs (i.e. la peinture) ;
  - Ajout d'une zone **Information** et NoSonar permettant d'afficher les informations générales sur le projet sélectionné :
    - Le nom ;
    - La clé ;
    - Le nombre de versions release ;
    - Le nombre de versions snapshot ;
    - Un bouton "Autre" qui permet d'afficher un graphique représentant la distribution des versions ;
    - La version de la dernière application analysée ;
    - La date de la dernière version analysée ;
    - Le nombre d'exclusions de type @supressWarbing et NoSonar ;
  - Ajout de la zone **Projet** permettant l'affichage des informations techniques du projet :
    - Le nombre de lignes de code ;
    - La couverture de tests ;
    - Le nombre de tests unitaire ;
    - Le niveau de duplications du code ;
    - Le nombre de défauts ;
    - La répartition de la dette technique : fiabilité, sécurité et mauvaise pratique ;
    - Les notes pour les indicateurs qualités et la répartition des hotspots ;
  - Ajout de la zone **Qualité** permettant d'afficher pour chaque indicateur qualité la distribution selon la sévérité ;
  - Ajout d'un bouton permettant d'aller sur la page **Sécurité** OWASP (en cours de migration) ;

- Création de la page **Profil** :
  - Ajout du header/footer ;
  - Ajout du fil d’Ariane ;
  - Affichage du tableau de répartition des règles par langage ;
  - Affichage de la distribution depuis un graphique ;

### Reste à faire

1. Afficher sur le page Home, les projets favoris ;
2. Ajouter la page OWASP Top 10 ;
3. Ajouter le Top 10 des violations ;
4. Ajouter un tableau consolidé permettant la distinction entre la partie frontend (présentation) et backend (métier) ;
5. Ajouter une timeline pour suivre les projets dans le temps et afficher les modifications ;
6. Ajouter les rapports PDF avec les éléments détaillés nécessaires pour les développeurs ;

## V0.0.1 : 24/01/2022

- Création du projet sous Symfony
- Reprise du projet développé eb HTML/CSS, Javascript + DexieJS ;
- Migration sous Symfony 5.4, php 8.1.0 ;
- Migration dexieJs vers SQLite 4 ;
- Migration du code en HTML/JS vers PHP, Twig ;
- Migration vers webpack ;
