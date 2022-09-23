# Gestion de la sécurité

![Ma-Moulinette](/documentation/ressources/home-000.jpg)

## Compte admin

Le compte **Admin** est ajouté par déafut en version  `1.5.0`. Son identifiant de connexion est  <admin@ma-moulinette.fr>. Son mot de passe est `-OuvreMoiL@Porte`.

L'utilisateur **Admin** permet d'accèder à la page de gestion des utilisateur. Une fois un gestionnaire applicatif assigné, il est **recommandé** de désactiver le compte en utilisant l'option **Actif** de la page d'édition du compte.

## Filtrage pas IP/Host

Le filtrage est activé par défaut depuis le fichier de configuration `framework.yaml'.

```yaml
trusted_hosts: ['%env(TRUST_HOST1)%','%env(TRUST_HOST2)%',]
```

`Note :` il est possible d'ajouter plusieurs HOST.

Par défaut, nous avons défini deux points de contrôle **TRUST_HOST1** et **TRUST_HOST2**, qui peuvent être utilisés pour filtrer :

- [ ] Une adresse IP :  `127.0.0.1`, `192.168.0.1`,... ;
- [ ] Une adresse DNS : `localhost`, `www.ma-petite-entreprise.fr` ;
- [ ] un domaine : `^ma-petite-entreprise\.fr$`

Il est nécessaire de définir dans le fichier **.env** la valeur de ces deux paramètres :

```yaml
TRUST_HOST1="^ma-petite-entrprise\.fr$"
TRUST_HOST2="10.0.0.1"
```

## Firewall

Le firewall dans Symfony permet de sécuriser par le biais de rôles, l'accès aux pages de l'applications.

Il existe deux rôles par défaut auquel nous avons ajouté deux rôles spécifiques.

- [x] **PUBLIC_ACCESS**, permet à l'accès aux pages publiques.
- [ ] **ROLE_USER**, permet dans Symfony l'accès à des pages privées.
- [x] **ROLE_UTILISATEUR**, permet l'accès à toutes les pages privées ayant se rôle.
- [x] **ROLE_GESTIONNAIRE**, permet l'accès aux pages de gestion de l'application.

Toute personne authentifiée peut accéder à l'ensemble des pages de l'application, à l'exception des pages destinées aux personnes ayant le rôle de `GESTIONNAIRE`.

Le tableau ci-dessous liste par rôle la liste des droits et des pages accessibles.

|    Page     | PUBLIC | UTILISATEUR | GESTIONNAIRE | URL                |
|-------------|:------:|:-----------:|:------------:|--------------------|
| Accueil     |   NON  |     OUI     |      OUI     | /home              |
| Inscription |   OUI  |     OUI     |      OUI     | /register          |
| Connexion   |   OUI  |     OUI     |      OUI     | /login             |
| Déconnexion |   OUI  |     OUI     |      OUI     | /logout            |
| Bienvenue   |   OUI  |     NON     |      OUI     | /welcome           |
| Dashboard   |   NON  |     OUI     |      OUI     | /admin             |
| Utilisateur |   NON  |     NON     |      OUI     | /admin?crudAction= |
| Projet      |   NON  |     OUI     |      OUI     | /projet            |
| Owasp       |   NON  |     OUI     |      OUI     | /owasp             |
| Suivi       |   NON  |     OUI     |      OUI     | /suivi             |
| Profil      |   NON  |     OUI     |      OUI     | /profil            |
| Repartition |   NON  |     OUI     |      OUI     | /repartition       |

Le fichier `security.yaml` contient la configuration suivante pour étendre les droits `UTILISATEUR` au `GESTIONNAIRE`.

```yaml
role_hierarchy:
        ROLE_GESTIONNAIRE: ROLE_UTILISATEUR
```

Les entrypoints sont définis de cette façon :

```yaml
  access_control:
      - { path: ^/login, roles: PUBLIC_ACCESS }
      - { path: ^/register, roles: PUBLIC_ACCESS }
      - { path: ^/welcome, roles: PUBLIC_ACCESS }
      - { path: ^/, roles: ROLE_UTILISATEUR }
```

## Filtrage twig et dans les controlleurs

Le contrôle des droits se fait au niveau des pages twig ou des contrôleurs.

Dans les pages HTML en TWIG :

```T
{% if is_granted('ROLE_UTILISATEUR') %} ...  {%end if%}

{% if is_granted('ROLE_GESTIONNAIRE') %} ...  {%end if%}
```

Dans les contrôleurs par l'ajout d'un attribut ou par l'utilisation de la méthode denyAccessUnlessGranted :

```T
#[IsGranted('ROLE_UTILISATEUR')]
```

```php
$this->denyAccessUnlessGranted('ROLE_GESTIONNAIRE', null, 'L\'utilisateur essaye d\'accèder à la page sans avoir le rôle ROLE_GESTIONNAIRE');
```

-**-- FIN --**-

[Retour au menu principal](/README.md)