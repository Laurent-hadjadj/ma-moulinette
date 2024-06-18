# Gestion de la sécurité

![Ma-Moulinette](/assets/images/home/home-000.jpg)

## Compte admin

Le compte **Admin** est ajouté par défaut en version  `1.5.0`.

- [x] Son identifiant de connexion est <admin@ma-moulinette.fr>.
- [x] Son mot de passe est : `eYK8k4[T;99N!em^`

> **Important** :
> Pour des raison de sécurité, le mot de passe du compte admin devra être changé à la première connexion.

L'utilisateur **Admin** permet d'accéder à la page de gestion des utilisateurs. Une fois qu'un gestionnaire applicatif est assigné, il est **recommandé** de désactiver le compte en utilisant l'option **Actif** de la page d'édition du compte.

## Filtrage pas IP/Host

Le filtrage est activé par défaut depuis le fichier de configuration `framework.yaml'.

```yaml
trusted_hosts: ['%env(TRUST_HOST1)%','%env(TRUST_HOST2)%',]
```

`Note :` il est possible d'ajouter plusieurs HOSTs.

Par défaut, nous avons défini deux points de contrôle **TRUST_HOST1** et **TRUST_HOST2**, qui peuvent être utilisés pour filtrer :

- [x] Une adresse IP :  `127.0.0.1`, `192.168.0.1`,... ;
- [ ] Une adresse DNS : `localhost`, `www.ma-petite-entreprise.fr` ;
- [ ] un domaine : `^ma-petite-entreprise\.fr$`

Il est nécessaire de définir dans le fichier **.env** la valeur de ces deux paramètres :

```yaml
TRUST_HOST1="^ma-petite-entreprise\.fr$"
TRUST_HOST2="10.0.0.1"
```

## La gestion de la sécurité

Le fichier `security.yml` contient le paramétrage de la sécurité et de l'authentification.

```yaml
  # On active le mécanisme d'authentification
    enable_authenticator_manager: true
    # On lève une exception si l'utilisateur n'existe pas
    hide_user_not_found: false
```

La hiérarchie des rôles permet l'héritage de droits.

```yaml
    role_hierarchy:
        ROLE_COLLECTE: ['ROLE_UTILISATEUR']
        ROLE_BATCH: ['ROLE_COLLECTE', ROLE_UTILISATEUR]
        ROLE_GESTIONNAIRE: ['ROLE_COLLECTE', 'ROLE_BATCH', ROLE_UTILISATEUR]
```

Les points d'accès sont définis de cette façon :

```yaml
  access_control:
   - { path: ^/login, roles: PUBLIC_ACCESS }
    - { path: ^/register, roles: PUBLIC_ACCESS }
    - { path: ^/welcome, roles: PUBLIC_ACCESS }
    - { path: ^/plan-du-site, roles: PUBLIC_ACCESS }
    - { path: ^/mentions-legales, roles: PUBLIC_ACCESS }
    - { path: ^/admin, roles: ROLE_UTILISATEUR }
    - { path: ^/, roles: ROLE_UTILISATEUR }
```

Le chiffrement utilise l'algorithme **brcrypt** par défaut avec un niveau de hachage de niveau 13.

```yaml
password_hashers:
        Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface:
            algorithm: 'bcrypt'
            cost:      13
```

## Firewall

Le firewall dans Symfony permet de sécuriser, par le biais de rôles, l'accès aux pages de l'application.

Il existe deux rôles par défaut auquel nous avons ajouté **trois** (3) rôles fonctionnels.

- [x] **PUBLIC_ACCESS**, permet à l'accès aux pages publiques.
- [ ] **ROLE_USER**, permet dans Symfony l'accès à des pages privés.
- [x] **ROLE_UTILISATEUR**, permet l'accès à toutes les pages privées ayant se rôle.
- [x] **ROLE_GESTIONNAIRE**, permet l'accès aux pages de gestion de l'application.
- [x] **ROLE_BATCH**, permet l'accès à la page de suivi des traitements automatique et manuel.

Toute personne authentifiée peut accéder à l'ensemble des pages de l'application, à l'exception des pages destinées aux personnes ayant le rôle de `GESTIONNAIRE` et `BATCH` (Traitement).

Le tableau ci-dessous liste par rôle les droits des pages accessibles.

|   Page      | PUBLIC | UTILISATEUR | COLLECTE | BATCH | GESTIONNAIRE | URL               |
|:-----------:|:------:|:-----------:|:--------:|:-----:|:------------:|:------------------|
| Accueil     | NON    | OUI         | -        | -     | -            | /home             |
| plan du site| OUI    | OUI         | -        | -     | -            | /plan-du-site     |
| Mentions    | OUI    | OUI         | -        | -     | -            | /mentions-legales |
| Inscription | OUI    | OUI         | -        | -     | -            | /register         |
| Connexion   | OUI    | OUI         | -        | -     | -            | /login            |
| Déconnexion | OUI    | OUI         | -        | -     | -            | /logout           |
| Reset passwd| NON    | OUI         | -        | -     | -            | reset/mot-de-passe|
| Bienvenue   | OUI    | NON         | -        | -     | -            | /welcome          |
| Dashboard   | NON    | OUI         | -        | -     | -            | /admin            |
| Utilisateur | NON    | NON         | -        | -     | OUI          | /admin?crudAction |
| Projet      | NON    | PARTIEL     | OUI      | OUI   | OUI          | /projet           |
| Owasp       | NON    | OUI         | -        | -     | -            | /owasp            |
| Suivi       | NON    | OUI         | -        | -     | -            | /suivi            |
| Profil      | NON    | PARTIEL     | OUI      | OUI   | OUI          | /profil           |
| Repartition | NON    | PARTIEL     | OUI      | OUI   | OUI          | /repartition      |
| Traitement  | NON    | NON         | -        | OUI   | OUI          | /traitement/suivi |

Le fichier `security.yaml` contient la configuration suivante pour étendre les droits du rôle :

- `UTILISATEUR` avec les droits de `COLLECTE`
- `UTILISATEUR` avec les droits de `BATCH`.
- `UTILISATEUR` avec les droits de `GESTIONNAIRE`.

## Filtrage twig et dans les contrôleurs

Le contrôle des droits se fait au niveau des pages twig ou des contrôleurs.

Dans les pages HTML en TWIG :

```T
{% if is_granted('ROLE_UTILISATEUR') %} ...  {%end if%}

{% if is_granted('ROLE_GESTIONNAIRE') %} ...  {%end if%}
```

Dans les contrôleurs par l'ajout d'un attribut ou par l'utilisation de la méthode denyAccessUnlessGranted :

```plaintext
#[IsGranted('ROLE_UTILISATEUR')]
```

```php
$this->denyAccessUnlessGranted('ROLE_GESTIONNAIRE', null, "L'utilisateur essaye d’accéder à la page sans avoir le rôle ROLE_GESTIONNAIRE");
```

-**-- FIN --**-

[Retour au menu principal](/index.html)
