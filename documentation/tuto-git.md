# Tutoriel d'utilisation de Git sur le terminal

## Prérequis

Un contexte sain pour utiliser Git serait que toutes les branches soient identiques au départ avant d'entamer le premier sprint.

## Les premières modifications

Vous venez de faire des modifications sur votre branche et pensez qu'un point de sauvegarde est nécessaire pour la bonne compréhension de votre avancée. Il faut faire ce qu'on appelle un commit.

### Étapes d'un commit

Lorsqu'on s'attaque à Git, il faut ne plus toucher au code et travailler uniquement avec Git jusqu'à la fin du commit.

>git status

Cette commande va nous servir à afficher tous les fichiers modifiés, ajoutés et supprimés.

**Exemple de git status vierge**

Sur cet exemple, la commande affiche la modification d'un fichier.

**Exemple de git status avec une modification**

Sur cet exemple, la commande affiche l'ajout d'un nouveau fichier.

**Exemple de git status avec un nouveau fichier**

>git add [nom du fichier]

Cette commande va nous servir à ajouter les fichiers que l'on veut dans le commit. Lorsqu'on ajoute un fichier avec cette commande, le git status nous marque en vert le ou les fichiers ajoutés grâce à celle-ci. **On dira qu'on suit les fichiers.**

**Exemple de git status avec un fichier ajouté**

Si on ajoute un fichier par erreur, on peut l'enlever en utilisant `git restore [nom du fichier]`.

Un raccourci utile avec ces deux commandes est `git add .` et `git restore .` qui va ajouter ou enlever tous les fichiers sans exception. Donc, si vous avez un ou plusieurs fichiers .old que vous ne voulez pas partager, il n'est pas possible d'utiliser `git add .`.

>git commit -m "Le message de votre commit"


Après avoir ajouté tous les fichiers que vous vouliez, on peut passer à la commande `git commit`.

Cette commande va ajouter un commit à votre branche suivi du message que vous avez mis.

**Exemple de commit**

### La mise en ligne de votre travail

>git push


Cette commande va envoyer tous les commits passés sur le dépôt en ligne de votre GitHub.

**Exemple de git push**

## La fusion des travaux communs

### Mise à jour de la branche develop

Pour bien commencer cette étape, il faut impérativement que la branche develop en local ait les dernières modifications du remote. Pour ce faire, on peut utiliser la commande suivante :

>git fetch

Cette commande va télécharger sans modifier les fichiers du projet en local.

Avec la commande

>git diff develop origin/develop

On pourra voir les différences entre le local et le remote. Pour valider le fetch, il faut entrer la commande suivante :

>git merge origin/develop

Après la résolution éventuelle de conflits, votre branche develop est à jour.

### Début de la fusion

Mettez-vous sur votre branche :

>git checkout [votre branche]

Puis faites la commande :

>git merge develop

Il y a deux cas : soit le merge se passe sans souci parce que le develop n'a pas été changé ou parce que les modifications apportées ne créent pas de conflit, soit le merge créera des conflits qu'il faudra résoudre.

Dans le cas où il y a des conflits, il faudra les resoudre puis faire [l'étape de commit](#etapes-dun-commit).

### Fusion de votre branche

Lorsque cela est fait, votre branche a vos modifications et celles de develop. Il ne vous reste plus qu'à merger votre branche develop.

Allez sur la branche develop `git checkout develop` et entrez merger votre branche :
git merge [votre branche]

## Commandes pratiques

>git log

Cette commande sert à voir l'historique des commits de tout le dépôt. Il y aura écrit HEAD pour vous signaler votre branche. Les branches avec le suffixe **origin/** sont les branches remote. Enfin, les branches avec seulement le nom sont les branches locales que vous avez.
