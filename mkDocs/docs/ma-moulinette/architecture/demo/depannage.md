# 🛠️ Dépannage et reste à faire

## 1️⃣ 🔍 Un symptôme, une cause

| Symptôme | Cause | Que faire |
| --- | --- | --- |
| La page **Clean Code est vide** | la table `clean_code` n'est alimentée que par la collecte interactive de la page projet ; un jeu de données préparé avant la correction (CHANGELOG 2.9.0) n'a aucune ligne, ou SonarQube est antérieur à la version 10 | relancer `preparer-donnees.sh` (la commande collecte maintenant ces indicateurs) puis envoyer le nouveau `demo.sql.gz` et lancer `restaurer-demo.sh`. Vérifier `lignes_clean_code` dans le tableau final |
| Un graphique n'a **qu'un seul point** | une seule version analysée dans SonarQube pour ce projet | analyser plusieurs `sonar.projectVersion`, puis [mettre à jour les données](mise-a-jour.md#nouvelles-donnees) |
| La version affichée est « not provided » | l'analyse n'avait pas de `sonar.projectVersion` | renseigner la propriété dans l'analyse SonarQube |
| `Permission denied` sur `/tmp/schema-demo.sql` pendant `restaurer-demo.sh` | sur Debian/Ubuntu, `fs.protected_regular` interdit à root d'écrire un fichier créé par un autre utilisateur dans `/tmp` | corrigé : le script écrit en utilisateur `postgres`. Mettre à jour le clone (nouveau tag) |
| `The "foundation-sites" vendor asset is missing` à la construction | `assets/vendor/` est ignoré par git et n'a pas été téléchargé | corrigé : `importmap:install` précède `asset-map:compile`. Utiliser `deployer-demo.sh` |
| `Interface … CompilerPassInterface not found` | autoloader construit avec `--classmap-authoritative` sur un montage qui omet des classes | corrigé : l'option n'est plus utilisée |
| Des fichiers d'exemple (`compose.yaml`, `base.html.twig`…) dans `app/` | Symfony Flex les dépose si Composer tourne dans le dossier du code, car `symfony.lock` n'est pas versionné | corrigé : Composer tourne dans un dossier qui ne contient que `composer.json` |
| Des messages `NOTICE: table … does not exist, skipping` à la restauration | `DROP TABLE IF EXISTS` du schéma sur une base neuve | corrigé : les `NOTICE` sont masqués, seuls avertissements et erreurs s'affichent |
| `PHP Warning: Module "fileinfo" is already loaded` | l'extension, déjà compilée dans l'image PHP, était ajoutée une seconde fois | corrigé dans `docker/php-custom.dockerfile` ; reconstruire l'image (`deployer-demo.sh` le fait) |
| La connexion ne « tient » pas, ou « hôte non autorisé » | accès par l'adresse IP du serveur : l'application n'accepte que `localhost` et `127.0.0.1`, et son cookie de session est « sécurisé » | ouvrir `http://localhost:8082/` par un [tunnel SSH](installation.md#verifier) |
| « Action indisponible dans l'instance de démonstration » | l'action est hors de la liste blanche du mode démo | normal pour une démo. Pour l'autoriser : [Sécurité et mode démo](securite.md#mode-demo-applicatif) |
| Pages **sans CSS ni JS** | `APP_DEBUG=0` : AssetMapper ne sert plus les assets, ils doivent être compilés | `asset-map:compile` est fait par la construction ; vérifier `app/public/assets/manifest.json` |
| **Ancien code** servi après un déploiement | cache Symfony `prod` conservé dans le volume `var` | le conteneur purge `var/cache` à chaque démarrage ; recréer les conteneurs (`up -d --force-recreate`) |
| Erreur 500 sur `/login` et toutes les pages | variables `LDAP_*` absentes | elles sont posées dans le compose (hôte invalide) ; vérifier que le compose n'a pas été modifié |
| `InvalidCipherKeyException` | `DEMO_APP_SECRET` n'est pas du base64 de 32 octets (`openssl rand -hex` donne 36 octets une fois décodé) | régénérer avec `openssl rand -base64 32` (ou relancer `deployer-demo.sh`) |
| L'application ne se connecte plus à la base après avoir modifié `.env` à la main | PostgreSQL ne relit pas ses mots de passe une fois son volume créé | relancer `deployer-demo.sh <tag>` (il applique `ALTER ROLE`) |
| Installation neuve en erreur à la création des index (`relation ma_moulinette.owasp_details does not exist`) | l'ancien `99_master_install.sql` n'incluait pas `20_tables/owasp_details.sql` | corrigé dans le script maître |
| `docker-php-ext-install opcache` échoue sous PHP 8.5 | extension déjà intégrée | retirée de la liste |

## 2️⃣ 🧭 Reste à faire avant ouverture

À faire avant d'exposer la démo sur Internet :

1. **Nom de domaine et certificat** : un nom public pour la démo, avec un certificat (un certificat joker par validation DNS est retenu pour la landing page).
2. **Valeurs du compose à adapter au nouveau nom** : `TRUST_HOST1`, `CGU_URL_SITE` et `APP_ALLOWED_ORIGINS` (aujourd'hui `localhost` / `127.0.0.1`), sans quoi l'application refuse les requêtes venant du nom public.
3. **Reverse proxy** : un hôte qui pointe sur `127.0.0.1:8081` du serveur (la pile n'écoute que sur cette adresse). Le TLS est porté par le reverse proxy.
4. **Lien depuis la landing page** `ma-moulinette.fr`.
5. **Restauration nocturne** planifiée sur l'hôte : [Installation initiale](installation.md#planifier-la-restauration-nocturne).
6. **Jeton SonarQube** : noter sa date d'expiration et prévoir son [renouvellement](mise-a-jour.md#renouveler-le-jeton-sonarqube).
7. **Jeu de données** : plusieurs versions par projet pour alimenter l'historique, avec Clean Code (voir le tableau final de `preparer-donnees.sh`).

!!! warning "Ne pas exposer avant le point 3"
    Tant que ces points ne sont pas faits, la démo n'écoute que sur `127.0.0.1` du serveur et ne doit pas être publiée.
