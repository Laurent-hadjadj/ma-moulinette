# 🔒 Sécurité et mode démo

La démo est **exposée à des visiteurs** : elle est donc isolée de la production, ne détient aucun secret réel et n'autorise qu'une liste fermée d'actions. Cette page décrit l'isolation, les secrets, le compte de démonstration et le mode démo applicatif.

## 1️⃣ 🧱 Isolation et jetons

| Élément | Valeur dans la démo | Effet |
| --- | --- | --- |
| `API_CLIENT_TOKEN`, `APP_CLIENT_TOKEN` | vides | `/api/public/traitement/automatique/*` répond **503** (échec fermé, voir [Gestion de la sécurité](../../developpement/securite.md)) |
| `DC_INGEST_TOKEN`, `ZAP_INGEST_TOKEN` | vides | dépôts de rapports Dependency-Check et ZAP fermés (503) |
| `SONAR_URL`, `SONAR_TOKEN` | `DEMO_SONAR_URL` / `DEMO_SONAR_TOKEN` de `docker/demo/.env` (ignoré par git) : compte `ma-moulinette-demo` en lecture seule (*Browse* sur les projets publics), jeton à durée limitée. Sans ces variables : adresse invalide, aucun appel sortant | lecture SonarQube seule ; les routes de collecte restent bloquées pour les visiteurs |
| `SONAR_ACTIVITY_*` | vides (ce jeton exige un compte administrateur) | la page Activité ne fonctionne pas en démo |
| `LDAP_HOST` | adresse invalide | pas d'annuaire : seuls les comptes locaux se connectent |
| `MAILER_DSN` | `null://null` | aucun courriel envoyé |
| `MAJ_PROJET`, `MAJ_PROFIL` | `0` | pas de mise à jour des listes depuis SonarQube |
| Mots de passe et secret applicatif | générés dans `docker/demo/.env` (ignoré par git) | jamais ceux de la production |

L'instance n'a **aucune route réseau vers la production** : réseau Docker dédié `ma-moulinette-demo-net`, pas de montage partagé.

## 2️⃣ 🔑 Secrets de `docker/demo/.env`

Tous sont à générer pour l'instance, jamais repris d'ailleurs. **Sur le serveur, `deployer-demo.sh` les génère pour vous** (voir « Qui les génère » ci-dessous) ; le tableau sert à comprendre leur forme, ou à les créer à la main. Le **type compte** : une clé de chiffrement ou une URL n'acceptent pas n'importe quelle chaîne.

| Variable | Type et génération | Contrainte | Où |
| --- | --- | --- | --- |
| `DEMO_POSTGRES_PASSWORD` | hexadécimal, `openssl rand -hex 24` (48 caractères) | aucune | serveur |
| `DEMO_DB_USER_PASSWORD` | **hexadécimal**, `openssl rand -hex 24` | ni `/`, ni `+`, ni `=` : il est inséré dans l'URL `DATABASE_URL` (le base64 en contient) | serveur |
| `DEMO_APP_SECRET` | **base64 de 32 octets**, `openssl rand -base64 32` (44 caractères) | sert à `APP_SECRET`, `SECRET`, `SALT` (toute longueur) **et** à `ACTUATOR_CIPHER_KEY`, qui doit décoder en exactement 32 octets : un hexadécimal de 48 caractères en donne 36 et déclenche `InvalidCipherKeyException` | serveur |
| `DEMO_USER_PASSWORD` | texte libre, mémorisable | communiqué aux visiteurs ; jamais celui d'un vrai compte | serveur |
| `DEMO_SONAR_URL` | adresse `https://` de l'instance SonarQube | vide = aucun appel sortant ; jamais commitée | poste (préparation), serveur si besoin |
| `DEMO_SONAR_TOKEN` | jeton utilisateur du compte `ma-moulinette-demo` | durée limitée : noter la date d'expiration | poste (préparation), serveur si besoin |
| `DEMO_PREPA_POSTGRES_PASSWORD`, `DEMO_PREPA_DB_USER_PASSWORD` | hexadécimal, `openssl rand -hex 24` | aucune | **poste uniquement** (base jetable de préparation, inutile sur le serveur) |

**Qui les génère ?** À chaque déploiement, `deployer-demo.sh` régénère `DEMO_POSTGRES_PASSWORD`, `DEMO_DB_USER_PASSWORD` et `DEMO_APP_SECRET`. Il **conserve** `DEMO_USER_PASSWORD` (communiqué aux visiteurs) et `DEMO_SONAR_*` (valeurs externes). Sur le **poste**, vous renseignez à la main `DEMO_SONAR_*` et `DEMO_PREPA_*` dans `docker/demo/.env`.

## 3️⃣ 👤 Compte de démonstration

`restaurer-demo.sh` applique `reference-demo.sql` après le chargement des données :

- le compte d'initialisation `admin@ma-moulinette.fr` des fixtures (`ROLE_INTERNAL`) est **supprimé** ;
- le compte `demo@ma-moulinette.fr` est créé avec le groupe utilisateur `ma-moulinette-demo` et le groupe fonctionnel `ma-moulinette` ;
- son mot de passe vient de `DEMO_USER_PASSWORD` (`docker/demo/.env`, ignoré par git) : seule son empreinte bcrypt entre en base.

Le groupe fonctionnel est un **préfixe de tag SonarQube** : l'application ne propose que les projets dont un tag commence par `ma-moulinette`. Le compte SonarQube `ma-moulinette-demo` ne voit, lui, que les projets sur lesquels il a *Browse* : les deux filtres se cumulent.

!!! danger "Rôles : `ROLE_UTILISATEUR` et `ROLE_SUIVI`, rien d'autre"
    Ne jamais donner `ROLE_GESTIONNAIRE` ni `ROLE_INTERNAL` au compte de démonstration : `ROLE_GESTIONNAIRE` ouvre le CRUD EasyAdmin des utilisateurs et des groupes, donc l'activation de comptes et l'attribution de rôles. Sans lui, le bouton de mise à jour du référentiel de la page d'accueil est inactif (aucun appel `accueil/tags`).

    `ROLE_SUIVI` n'ouvre que deux routes (`suivi/version/reference` et `suivi/version/poubelle`) : il permet de **choisir la version de référence** d'un projet dans la modale de modification de la page de suivi. La corbeille (suppression d'une version) reste interdite par le mode démo. La version de référence est une donnée **partagée** : un visiteur la change pour tous jusqu'à la restauration de la nuit.

## 4️⃣ 🚧 Mode démo applicatif

Avec `APP_MODE_DEMO=1`, `DemoModeSubscriber` refuse toute requête absente de la liste blanche `ModeDemo`, **avant** le routage et le pare-feu (priorité 100) : une route inconnue, ajoutée plus tard ou protégée par un jeton est donc refusée elle aussi. Hors démo (`APP_MODE_DEMO=0`, valeur par défaut), le mécanisme est inerte.

Un filtre par méthode HTTP ne suffit pas : de nombreuses lectures sont en `POST` (`/api/secure/peinture/*`, listes de versions) et certaines routes `GET` écrivent (`/profil/details`, `/repartition`). La liste est donc écrite route par route, après lecture de chaque contrôleur.

| Famille | Autorisé | Interdit (exemples) |
| --- | --- | --- |
| Pages | accueil, projet, suivi, OWASP, Clean Code, Dependency-Check, ZAP, activité, statistiques projet, préférences et profil en lecture, pages légales, page « Action indisponible », connexion/déconnexion | `/admin/*` (le menu ouvre directement les préférences), `/actuator/*`, `/rapports`, `/traitement/*`, `/statistiques/dashboard`, `/statistiques/utilisateur`, `/profil/details`, `/repartition`, `/mot-de-passe/mise-a-jour` |
| API en lecture | `/api/secure/peinture/*`, listes de projets et de versions, `quality/langage`, `quality/off`, `activity/dessin`, `favori/check`, `accueil/tags`, `status`, `health`, `system/info`, `preference/category` | `collecte/*`, `enregistrement`, `accueil/projet`, `quality/profiles`, `favori`, `suivi/version/suivi`, `suivi/mise-a-jour`, `activity/sauvegarde`, `traitement/*`, `repartition/*` |
| Écritures tolérées | `POST /login` (ouverture de session) ; `POST utilisateur/change-me` (avatar) ; `POST preference/statut` (préférences : vue par défaut de la page projet, suivi, favoris) ; `PUT suivi/version/reference` (version de référence d'un projet) | tout le reste, dont `preference/favori/delete`, `preference/version/delete`, `suivi/version/poubelle` (suppression) et `suivi/version/favori` |
| Dépôts et jetons | — | `dependency-check/upload`, `zap/upload`, `/api/public/traitement/*` |

Les écritures tolérées ne touchent, sauf la version de référence, que le compte de l'utilisateur connecté, sur une base restaurée chaque nuit. **La version de référence est une donnée partagée** (table `historique`) : un visiteur la modifie pour tous jusqu'à la restauration. La route exige `ROLE_SUIVI`, rôle donné au compte de démonstration.

### Ce que voit l'utilisateur lorsqu'une action est refusée

- **page** : redirection vers `/demo/indisponible`, rendue avec le gabarit de l'application (en-tête, pied de page, logo de la démonstration, lien de retour) ;
- **appel de l'interface** (en-tête `X-Api-Custom-403`, envoyé par les écrans) : HTTP 200 et `{"x-api-custom-403":true,"code":403,"type":"warning","message":"Action indisponible…"}`, affiché dans le bandeau de message de la page, comme pour un rôle insuffisant ;
- **autre appel d'API** : HTTP 403 et `{"code":403,"type":"warning","message":"…"}`.

Chaque refus écrit un avertissement `[Démo]` dans les journaux (méthode, chemin, IP).

### Identité visuelle

`LOGO_ENTREPRISE=logo-demo.png` (`assets/images/logo-demo.png`) remplace le logo d'entreprise, et `ENVIRONNEMENT=DEMO` affiche l'étiquette **verte** `DEMO` à côté de la version (rouge `DEV`, bleue `REC`, grise `PRD`).

!!! note "Étendre la liste"
    Ajouter un motif dans `ModeDemo` **seulement** après avoir lu le contrôleur (écriture limitée au compte de l'utilisateur ou sans gravité pour une donnée partagée, aucun appel sortant hors `SONAR_URL`), et compléter `ModeDemoTest` (cas autorisé et cas voisin interdit, y compris les autres méthodes HTTP).
