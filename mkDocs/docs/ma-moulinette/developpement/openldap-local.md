# Annuaire OpenLDAP local

![Ma-Moulinette](/assets/images/home/home-000.jpg)

## Pourquoi un LDAP local ?

L'application `ma-moulinette` s'authentifie via l'`App\Security\CustomAuthenticator` qui interroge un annuaire LDAP. En production, il s'agit d'un Active Directory d'entreprise. Pour les tests unitaires et d'intégration, on monte un OpenLDAP local sur le poste développeur :

- pas de dépendance réseau ;
- DIT (Directory Information Tree) maîtrisé et reproductible ;
- jeu d'utilisateurs de test stable.

## Distribution utilisée

On utilise la build **OpenLDAP for Windows** de **Maxcrc** (binaire `slapd.exe`).

- Emplacement : `c:\environnement\0_toolz\openLdap\`
- Backend retenu : **MDB** (et **non BDB**)

> ⚠️ La build BDB de Maxcrc échoue au démarrage avec `bdb_db_open: alock_recover failed` même sur un répertoire `data/` vide. Le backend MDB fonctionne sans correctif et reste suffisant pour des tests.

## Configuration `slapd.conf`

Le fichier `c:\environnement\0_toolz\openLdap\slapd.conf` est ajusté ainsi :

```text
database    mdb
maxsize     1073741824
suffix      "dc=ma-moulinette,dc=fr"
rootdn      "cn=Manager,dc=ma-moulinette,dc=fr"
rootpw      {SSHA}oZ11ATPotKAGo59ScY5ahJoXFhoIsOqx
directory   ./data

index mail        pres,eq
index objectclass pres
index default     eq,sub
index sn          eq,sub,subinitial
index cn          eq,sub
index uid         eq
```

Le mot de passe `rootpw` correspond à **`secret`** (hash SSHA généré avec `slappasswd.exe -s secret`).

## Structure du DIT

```plaintext
dc=ma-moulinette,dc=fr
├── ou=utilisateurs            ← comptes techniques (bind technique)
│     └── cn=svc_dev           ← compte de service utilisé par l'application
└── ou=developpement           ← LDAP_BASE_DN (utilisateurs autorisés)
      ├── uid=laurent.hadjadj
      ├── uid=aurelie.petit-coeur
      └── uid=nathan.jones
```

## Bootstrap LDIF

Le fichier `c:\environnement\0_toolz\openLdap\bootstrap-ma-moulinette.ldif` contient :

- la racine `dc=ma-moulinette,dc=fr` ;
- les deux unités d'organisation `ou=utilisateurs` et `ou=developpement` ;
- le compte de service `cn=svc_dev` (mot de passe : `secret`, en clair pour les tests) ;
- trois utilisateurs de test (`inetOrgPerson`) :

  - `laurent.hadjadj` / `TestPassword!1` (admin)
  - `aurelie.petit-coeur` / `TestPassword!2` (gestionnaire)
  - `nathan.jones` / `TestPassword!3` (collecte)

## Variables d'environnement

À positionner dans `.env.local` :

```dotenv
LDAP_HOST="localhost"
LDAP_PORT=389
LDAP_ENCRYPTION="none"
LDAP_UPN_SUFFIX="@ma-moulinette.fr"
LDAP_BASE_DN="ou=developpement,dc=ma-moulinette,dc=fr"
LDAP_BIND_DN="cn=svc_dev,ou=utilisateurs,dc=ma-moulinette,dc=fr"
LDAP_BIND_PASSWORD="secret"
LDAP_X_TLS_REQUIRE_CERT=0
```

## Démarrage du serveur

Depuis `c:\environnement\0_toolz\openLdap\` :

```powershell
# (1) initialisation : data/ doit exister et être vide la première fois
Remove-Item data -Recurse -Force
New-Item    data -ItemType Directory

# (2) démarrage en mode console (logs visibles)
.\slapd.exe -d 256 -f .\slapd.conf -h "ldap://0.0.0.0:389/"
```

Pour un démarrage en service Windows, voir `install-slapd.cmd` fourni par Maxcrc.

## Chargement du DIT de test

Depuis `c:\environnement\0_toolz\openLdap\ClientTools\` :

```cmd
ldapadd.exe -x -H ldap://localhost ^
            -D "cn=Manager,dc=ma-moulinette,dc=fr" ^
            -w secret ^
            -f c:\environnement\0_toolz\openLdap\bootstrap-ma-moulinette.ldif
```

Sept entrées doivent être ajoutées (racine + 2 OU + 1 compte de service + 3 utilisateurs).

## Vérifications

> Bind technique (compte de service)

```cmd
ldapsearch.exe -x -H ldap://localhost ^
               -D "cn=svc_dev,ou=utilisateurs,dc=ma-moulinette,dc=fr" ^
               -w secret ^
               -b "ou=developpement,dc=ma-moulinette,dc=fr" ^
               "(uid=laurent.hadjadj)"
```

> Bind utilisateur

```cmd
ldapwhoami.exe -x -H ldap://localhost ^
               -D "uid=laurent.hadjadj,ou=developpement,dc=ma-moulinette,dc=fr" ^
               -w "TestPassword!1"
```

## Vérification depuis Symfony (`app:ldap:test`)

Une commande Symfony dédiée valide directement la configuration `.env.local` (`LDAP_HOST`, `LDAP_PORT`, `LDAP_ENCRYPTION`, `LDAP_BIND_DN`, `LDAP_BIND_PASSWORD`) en effectuant un *bind* technique :

```bash
php bin/console app:ldap:test
```

Sortie attendue lorsque le serveur OpenLDAP local tourne et que le compte de service `cn=svc_dev,...` est correctement injecté :

```text
✅ Connexion LDAP OK
```

En cas d'échec, la commande affiche le message d'erreur, le code retour LDAP et la trace pour faciliter le diagnostic (port fermé, mauvais DN de bind, mot de passe incorrect, certificat TLS rejeté, etc.).

> Source : `src/Command/LdapTestCommand.php`.

## Adaptation du `CustomAuthenticator`

L'authenticator actuel est calibré pour Active Directory :

```php
// src/Security/CustomAuthenticator.php
$filter = sprintf('(&(objectClass=user)(sAMAccountName=%s))', $samAccountName);
```

Pour fonctionner sur OpenLDAP avec le DIT ci-dessus, deux objectClass / attributs n'existent pas (`user`, `sAMAccountName`). Il faudra paramétrer le filtre par environnement, par exemple :

```dotenv
LDAP_USER_OBJECTCLASS="inetOrgPerson"
LDAP_USER_FILTER_ATTR="uid"
```

et reconstruire le filtre dans l'authenticator à partir de ces variables. Cette adaptation reste à faire et est suivie dans la phase de mise en place des tests d'intégration LDAP.

## Limites connues

- pas de TLS (`LDAP_ENCRYPTION="none"`, port 389) ; suffisant pour des tests locaux ;
- mots de passe utilisateurs stockés en clair dans le LDIF de bootstrap (acceptable car DIT recréé à chaque test) ;
- la build Maxcrc ne fournit pas tous les outils GNU OpenLDAP : `slappasswd.exe`, `ldapadd.exe`, `ldapsearch.exe`, `ldapwhoami.exe` sont disponibles dans `ClientTools\`.
