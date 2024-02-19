# Gestion des équipes

![Ma-Moulinette](/documentation/ressources/home-000.jpg)

## Backoffice de gestion

* [Dashboard](/documentation/indicateurs.md)
* [Utilisateur](/documentation/utilisateur.md)
* [**Equipe**](/documentation/equipe.md)
* [Portefeuille](/documentation/portefeuille.md)
* [Batch](/documentation/batch.md)

La gestion des **équipes** s'appuie sur un contrôleur CRUD du bundle EasyAdmin. Les options disponibles sont les suivantes :

* [X] Je peux visualiser la liste des équipes projets ;
* [X] Je peux afficher le détail de l'équipe ;
* [X] Je peux modifier le titre et la description de l'équipe ;
* [X] Je peux supprimer une équipe ;

L'entité `Èquipe` permet de regrouper des personnes travaillant sur les mêmes projets.

## Accéder à l'interface d'administration

Il faut avoir le rôle `GESTIONNAIRE` et cliquer sur l'icône utilisateurs en haut à droite.

![utilisateur-icône](/documentation/ressources/utilisateur-001.jpg)

Puis, depuis le menu latéral, cliquez sur l'icône **équipe**.
![equipe-icône](/documentation/ressources/equipe-000.jpg)

## Afficher la liste des équipes

Par exemple, ci-dessous, la liste est vide.

![equipe-liste](/documentation/ressources/equipe-001.jpg)

Pour chaque équipe, le tableau affiche les éléments suivants  :

* [ ] Le titre ;
* [ ] La description ;
* [ ] La date de modification ;
* [ ] La date de création ;

Par défaut il existe deux équipes :

1. `AUCUNE` - Personne de m'aime !
2. `MA MOULINETTE`- Développement de l'application Ma-Moulinette

![equipe-liste](/documentation/ressources/equipe-001a.jpg)

Si, je ne veux pas être rattaché à aucune équipe, je dois choisir **AUCUNE**.

L'équipe **MA MOULINETTE** est l'équipe des testeurs de l'application `Ma Moulinette`.

Le menu en fin de ligne permet de (consulter, éditer et supprimer l'équipe).

![equipe-menu](/documentation/ressources/utilisateur-003.jpg)

## Ajouter une nouvelle équipe

Il suffit de cliquer sur le bouton **Créer Equipe** en haut à droite de l'écran. En suite, il vous suffira de saisir le `titre` de l'équipe et donner une `description`.

![equipe-ajouter](/documentation/ressources/equipe-002.jpg)

Puis cliquez sur le bouton `Créer` pour valider le formulaire.

![equipe-erreur](/documentation/ressources/equipe-003.jpg)

`Attention.` **Le titre** de l'équipe doit être **unique**.

![utilisateur](/documentation/ressources/equipe-004.jpg)

`Note :` Initialement, l'attribut `#[UniqueEntity...]` était utilisé au niveau de la class pour contrôler l'unicité de l'attribut `Titre` mais son utilisation n'étant assez fiable (i.e. son fonctionnement n'est pas garanti), une contrainte de validité `#[AcmeAssert\ContainsEquipeUnique()]` a été ajouté au niveau de l'attribut lui même. Un contrôle a été ajouté également au niveau du contrôler CRUD.

Le code avant :

```php
#[ORM\Entity(repositoryClass: EquipeRepository::class)]
#[UniqueEntity(fields: ['titre'], message: 'Le {{ label }} : {{ value }} existe déjà.')]
class Equipe
{
  #[ORM\Column(name: 'titre', type: 'string', length: 32, unique: true)]
  private $titre;
}
```

Le code a été modifié comme suit :

```php
#[ORM\Entity(repositoryClass: EquipeRepository::class)]
class Equipe
{
  #[ORM\Column(name: 'titre', type: 'string', length: 32, unique: true)]
  #[AcmeAssert\ContainsEquipeUnique()]
  private $titre;
}
```

## Consulter l'équipe

Il est possible de :

* [x] **supprimer** l'équipe ;
* [x] **revenir à la liste** ;
* [x] **éditer** l'équipe ;

![equipe-consulter](/documentation/ressources/equipe-005.jpg)

## Editez l'équipe

Il est possible de :

* [x] Modifier le titre de l'équipe ;
* [x] Modifier la description ;

![equipe-editer](/documentation/ressources/equipe-006.jpg)

Pour valider la modification, il suffit de cliquer sur le bouton `Sauvegarder les modifications`.

## Messages utilisateurs

* Ajout d'une nouvelle équipe.
![equipe-message](/documentation/ressources/equipe-007.jpg)

* L'équipe existe déjà.
![equipe-editer](/documentation/ressources/equipe-008.jpg)

* Suppression de l'équipe.
![equipe-editer](/documentation/ressources/equipe-009.jpg)

* Mise à jour de l'équipe.
![equipe-editer](/documentation/ressources/equipe-010.jpg)

-**-- FIN --**-

[Retour au menu principal](/README.md)
