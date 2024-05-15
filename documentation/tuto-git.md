# Tuto d'utilisation de git sur le terminal

## Prérequis

Un contexte sain à l'utilisation de Git serait que toutes les branches soient identiques au départ avant d'entamer le premier sprint.

## Les premières modifications

Vous venez de faire des modifications sur votre branche et pensez qu'un piont de sauvegarde est necessaire pour la bonne compréhension de votre avancez. Il faut faire se qu'on appel un commit.

### Etapes d'un commit

Lorsqu'on s'attaque a git il faut ne plus toucher au code et travail uniquement avec git jusqu'à la fin du commit

>git status

Cette commande va nous servir pour afficher tout les fichier modifier, ajouter et supprimer

*Exmple de git status vierge*

Sur cette exmple la commande affiche la modification d'un fichier

*Exemple de git status avec un modification*

Sur cette exemple la commande affiche l'ajout d'un nouveau fichier fichier

*Exemple de git status avec un nouveau fichier*


> git add [nom du fichier]

Cette commande va nous servir pour ajouter les fichier que l'on veut dans le commit. Lorsqu'on ajoute un fichier avec cette commande, le git status nous marque en vert le ou les fichiers ajouter grâce à celle-ci. **On dira qu'on suit les fichiers**

*Exemple de git status avec un fichier add*

Si on ajoute un fichier par erreur on peut l'enlever en utilisant `git restore [nom du fichier]`

Un raccourci utile avec ces deux commande est `git add .` et `git restore .` qui va ajouter ou enlever tout les fichiers sans exception. Donc si vous avez un ou plusieurs fichier .old que vous ne voulez pas partager il n'est pas possible d'utiliser `git add .`


>git commit -m "*Le message de votre commit*"

Après avoir ajouter tout les fichiers que vous vouliez on peut passer à la commande `git commit`.

Cette commande va ajouter un commit à votre branche suivit du message que vous avez mis

*Exemple de commit*

### La mise en ligne de votre travail

>git push

Cette commande va envoyer tout les commits passer sur le repo en ligne de votre github.
*Exemple de git push*

## La fusion des travaux commun

Pour bien commencer cette etape il faut imperativement que la branche develop 
