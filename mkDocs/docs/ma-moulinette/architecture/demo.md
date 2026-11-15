# 🎭 Instance de démonstration

Une instance **séparée** de Ma-Moulinette permet de présenter l'application avec les données des projets open source publics de l'auteur, sans jamais toucher à la production. Elle vit dans `docker/demo/` et ne partage avec la production ni base, ni réseau, ni volume, ni jeton.

!!! caution "⚠️ État d'avancement : pile et mode démo livrés, jeu de données à constituer"
    La pile Docker et le **mode démo applicatif** (`APP_MODE_DEMO=1`, liste blanche de routes, refus par défaut) sont opérationnels. Il reste à constituer le jeu de données, à supprimer le compte d'initialisation et à publier le nom (voir « Reste à faire »). Tant que ce n'est pas fait, la pile n'est publiée que sur `127.0.0.1` et **ne doit pas être exposée sur Internet**.

## 🧱 Composition de la pile

| Conteneur | Rôle | Particularités |
| --- | --- | --- |
| `postgres-ma-moulinette-demo` | Base PostgreSQL 18 dédiée | Volume `ma-moulinette-demo-pgdata`, aucun port publié |
| `php-fpm-ma-moulinette-demo` | Application (`APP_ENV=prod`, `APP_DEBUG=0`, sans dépendances de développement) | Assets compilés à la construction ; aucun fichier `.env.local` dans l'image |
| `nginx-ma-moulinette-demo` | Serveur HTTP | Publié sur `127.0.0.1:8081` uniquement ; HTTP interne, le TLS est porté par le reverse proxy de l'hôte |

**Aucun conteneur cron** : aucun traitement batch ne tourne dans la démo, et aucune purge de journaux n'est planifiée. Les données ne changent que par la restauration décrite plus bas.

## 🔒 Isolation et jetons

| Élément | Valeur dans la démo | Effet |
| --- | --- | --- |
| `API_CLIENT_TOKEN`, `APP_CLIENT_TOKEN` | vides | `/api/public/traitement/automatique/*` répond **503** (échec fermé, voir [Gestion de la sécurité](../developpement/securite.md)) |
| `DC_INGEST_TOKEN`, `ZAP_INGEST_TOKEN` | vides | dépôts de rapports Dependency-Check et ZAP fermés (503) |
| `SONAR_URL`, `SONAR_TOKEN` | `DEMO_SONAR_URL` / `DEMO_SONAR_TOKEN` de `docker/demo/.env` (ignoré par git) : compte `ma-moulinette-demo` en lecture seule (*Browse* sur les projets publics), jeton à durée limitée. Sans ces variables : adresse invalide, aucun appel sortant | lecture SonarQube seule ; les routes de collecte restent bloquées pour les visiteurs |
| `SONAR_ACTIVITY_*` | vides (ce jeton exige un compte administrateur) | la page Activité ne fonctionne pas en démo |
| `LDAP_HOST` | adresse invalide | pas d'annuaire : seuls les comptes locaux se connectent |
| `MAILER_DSN` | `null://null` | aucun courriel envoyé |
| `MAJ_PROJET`, `MAJ_PROFIL` | `0` | pas de mise à jour des listes depuis SonarQube |
| Mots de passe et secret applicatif | générés dans `docker/demo/.env` (ignoré par git) | jamais ceux de la production |

L'instance n'a **aucune route réseau vers la production** : réseau Docker dédié `ma-moulinette-demo-net`, pas de montage partagé.

## 🚀 Démarrage

```bash
cp docker/demo/.env.exemple docker/demo/.env     # puis remplacer chaque valeur : openssl rand -hex 24
docker compose -f docker/demo/docker-compose.demo.yml up -d --build
bash docker/demo/restaurer-demo.sh               # première remise à l'état de référence
# http://127.0.0.1:8081
```

Au premier démarrage du volume PostgreSQL vide, `init-schema.sh` crée le rôle, la base `ma_moulinette` et rejoue le schéma. `assembler-schema.sh` assemble le SQL à partir de `migrations/POSTGRESQL/99_master_install.sql` en **excluant** :

- les trois scripts `00_init` de création (locale Windows absente de l'image Linux) ;
- `90_fixtures/fixtures-e2e.sql` : il crée des comptes de test aux mots de passe connus, qui n'ont rien à faire dans une instance exposée.

## 👤 Compte de démonstration

`restaurer-demo.sh` applique `reference-demo.sql` après le chargement des données :

- le compte d'initialisation `admin@ma-moulinette.fr` des fixtures (`ROLE_INTERNAL`) est **supprimé** ;
- le compte `demo@ma-moulinette.fr` est créé avec le groupe utilisateur `ma-moulinette-demo` et le groupe fonctionnel `ma-moulinette` ;
- son mot de passe vient de `DEMO_USER_PASSWORD` (`docker/demo/.env`, ignoré par git) : seule son empreinte bcrypt entre en base.

Le groupe fonctionnel est un **préfixe de tag SonarQube** : l'application ne propose que les projets dont un tag commence par `ma-moulinette`. Le compte SonarQube `ma-moulinette-demo` ne voit, lui, que les projets sur lesquels il a *Browse* : les deux filtres se cumulent.

!!! danger "Rôle : `ROLE_UTILISATEUR` uniquement"
    Ne jamais donner `ROLE_GESTIONNAIRE` ni `ROLE_INTERNAL` au compte de démonstration : `ROLE_GESTIONNAIRE` ouvre le CRUD EasyAdmin des utilisateurs et des groupes, donc l'activation de comptes et l'attribution de rôles. Avec le rôle minimal, le bouton de mise à jour du référentiel de la page d'accueil est inactif (aucun appel `accueil/tags`).

## 🚧 Mode démo applicatif

Avec `APP_MODE_DEMO=1`, `DemoModeSubscriber` répond **403** à toute requête absente de la liste blanche `AppSecurityModeDemo`, **avant** le routage et le pare-feu (priorité 100) : une route inconnue, ajoutée plus tard ou protégée par un jeton est donc refusée elle aussi. Hors démo (`APP_MODE_DEMO=0`, valeur par défaut), le mécanisme est inerte.

Un filtre par méthode HTTP ne suffit pas : de nombreuses lectures sont en `POST` (`/api/secure/peinture/*`, listes de versions) et certaines routes `GET` écrivent (`/profil/details`, `/repartition`). La liste est donc écrite route par route, après lecture de chaque contrôleur.

| Famille | Autorisé | Interdit (exemples) |
| --- | --- | --- |
| Pages | accueil, projet, suivi, OWASP, Clean Code, Dependency-Check, ZAP, activité, statistiques projet, préférences et profil en lecture, pages légales, connexion/déconnexion | `/admin/*`, `/actuator/*`, `/rapports`, `/traitement/*`, `/statistiques/dashboard`, `/statistiques/utilisateur`, `/profil/details`, `/repartition`, `/mot-de-passe/mise-a-jour` |
| API en lecture (`POST`) | `/api/secure/peinture/*`, listes de projets et de versions, `quality/langage`, `quality/off`, `activity/dessin`, `favori/check`, `accueil/tags`, `status`, `health`, `system/info` | `collecte/*`, `enregistrement`, `accueil/projet`, `quality/profiles`, `favori`, `suivi/version/suivi`, `suivi/mise-a-jour`, `preference/*`, `activity/sauvegarde`, `traitement/*`, `repartition/*` |
| Dépôts et jetons | — | `dependency-check/upload`, `zap/upload`, `/api/public/traitement/*` |
| Seule écriture tolérée | `POST /login` (ouverture de session) | tout le reste |

Le refus renvoie du JSON (`{"code":403,"type":"demo",...}`) pour les appels d'API ou Ajax, une page HTML sinon, et écrit un avertissement `[Démo]` dans les journaux (méthode, chemin, IP).

!!! note "Étendre la liste"
    Ajouter un motif dans `ModeDemo` **seulement** après avoir lu le contrôleur (aucune écriture en base, aucun appel sortant hors `SONAR_URL`), et compléter `ModeDemoTest` (cas autorisé et cas voisin interdit).

## 🔄 Restauration nocturne

`restaurer-demo.sh` remet la base à l'état de référence : arrêt de l'application (la base reste lancée), recréation de la base, rejeu du schéma, chargement de `docker/demo/donnees/demo.sql.gz` s'il existe, redémarrage. Il se lance **depuis l'hôte**, par exemple chaque nuit avec son planificateur : la démo n'embarque aucun cron. Ainsi, toute modification faite par un visiteur (préférences, mot de passe, suivi) disparaît à la restauration suivante.

Le dossier `docker/demo/donnees/` est ignoré par git : le jeu de données (projets publics, historique) est un artefact de l'instance, pas du code. Il se constitue avec la procédure ci-dessous.

## 📥 Alimenter les données

La collecte ne tourne **jamais** dans la démo (pas de cron, pas de jeton de collecte). Elle se fait dans l'environnement de développement, dans une base à part, puis s'exporte vers un fichier unique.

```bash
bash docker/demo/preparer-donnees.sh                       # tous les projets du tag « ma-moulinette »
bash docker/demo/preparer-donnees.sh --projet fr.exemple:mon-projet   # un seul projet (répétable)
bash docker/demo/preparer-donnees.sh --liste-seulement     # voir les projets retenus, sans rien collecter
bash docker/demo/preparer-donnees.sh --export-seulement    # réexporter sans relancer la collecte
bash docker/demo/preparer-donnees.sh --reinitialiser       # repartir d'une base de préparation vide
bash docker/demo/restaurer-demo.sh                         # charger le résultat dans la démo
```

Le script :

1. démarre `docker-compose.prepa.yml` : un PostgreSQL jetable publié sur **`127.0.0.1:5435`**, sans lien avec la production, le développement ou la démo ;
2. lance `php bin/console app:demo:preparer` en `APP_ENV=dev`, avec `DEMO_SONAR_URL` / `DEMO_SONAR_TOKEN` (compte de **lecture seule**) imposés par variables d'environnement : aucune base ni jeton de l'hôte n'est utilisé ;
3. exporte en un seul fichier les tables portant une clé de projet, plus `profiles` et `properties` (`TRUNCATE … CASCADE` puis `COPY`).

`app:demo:preparer` enchaîne : liste des projets visibles par le compte SonarQube (`liste_projet`), profils de qualité, puis pour chaque projet dont un tag commence par le préfixe demandé la collecte complète et la reconstruction de l'historique par version. Il rend un code d'erreur si une étape échoue, et le script n'exporte pas une base dont l'historique est vide.

!!! danger "Garde-fou"
    La commande vide et réécrit des tables : elle **refuse de s'exécuter** hors `APP_ENV=dev` et pour toute connexion autre que l'hôte local sur le port 5435 (`DemoPreparerCommand::baseAutorisee`). Ne la lancez que par `preparer-donnees.sh`.

Côté SonarQube, le compte de la démo ne voit que les projets sur lesquels il a *Browse* ; le tag SonarQube des projets doit commencer par le préfixe du groupe fonctionnel (`ma-moulinette`). `verifier-sonar.sh` contrôle en lecture seule que ce compte n'a aucun droit global.

!!! note "Historique par version"
    L'historique n'a autant de lignes que SonarQube a de **versions analysées** (`sonar.projectVersion` distinct). Une seule analyse donne une seule ligne : analysez plusieurs versions pour obtenir une courbe.

## 🧭 Reste à faire avant ouverture

1. **Jeu de données** : analyser plusieurs versions par projet pour alimenter l'historique, puis relancer la préparation. Le compte de démonstration et la suppression du compte d'initialisation sont déjà en place (voir ci-dessus).
2. **Publication** : nom d'hôte public, `TRUST_HOST1` / `CGU_URL_SITE` à ajuster, réseau du reverse proxy, certificat.
3. **Lien depuis la landing page** `ma-moulinette.fr`.

## 🛠️ Pièges rencontrés

| Piège | Conséquence | Contournement |
| --- | --- | --- |
| `99_master_install.sql` n'incluait pas `20_tables/owasp_details.sql` | Installation neuve en erreur à la création des index (`relation ma_moulinette.owasp_details does not exist`) | Ligne ajoutée au script maître |
| Variables `LDAP_*` absentes | Erreur 500 sur `/login` et toutes les pages | Posées dans le compose (hôte invalide) |
| `APP_DEBUG=0` : AssetMapper ne sert plus les assets dynamiquement | Pages sans CSS ni JS | `asset-map:compile` à la construction ; l'image nginx copie `public/` de l'image PHP |
| Cache Symfony `prod` conservé dans le volume `var` d'une reconstruction à l'autre | Ancien code servi (le filtre démo semblait sans effet) | `rm -rf var/cache/*` à chaque démarrage du conteneur (entrypoint) |
| `docker-php-ext-install opcache` sous PHP 8.5 | Échec de la construction (extension déjà intégrée) | Retirée de la liste |
