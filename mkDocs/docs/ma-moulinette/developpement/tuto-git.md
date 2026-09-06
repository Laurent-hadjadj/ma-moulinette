# 🌿 Tutoriel d'utilisation de Git sur le terminal

## ✅ Prérequis

Un contexte sain pour utiliser Git serait que toutes les branches soient identiques au départ avant d'entamer le premier sprint.

## 📝 Les premières modifications

Vous venez de faire des modifications sur votre branche et pensez qu'un point de sauvegarde est nécessaire pour la bonne compréhension de votre avancée. Il faut faire ce qu'on appelle un commit.

### Étapes d'un commit

Lorsqu'on s'attaque à Git, il faut ne plus toucher au code et travailler uniquement avec Git jusqu'à la fin du commit.

```bash
git status
```

Cette commande affiche tous les fichiers modifiés, ajoutés et supprimés. Exemple avec un fichier modifié :

```text
On branch develop
Changes not staged for commit:
  (use "git add <file>..." to update what will be committed)
  (use "git restore <file>..." to discard changes in working directory)
        modified:   src/Controller/ProjetController.php

no changes added to commit (use "git add" and/or "git commit -a")
```

Et avec un nouveau fichier non suivi :

```text
Untracked files:
  (use "git add <file>..." to include in what will be committed)
        src/Service/NouveauService.php
```

```bash
git add [nom du fichier]
```

Cette commande va nous servir à ajouter les fichiers que l'on veut dans le commit. Lorsqu'on ajoute un fichier avec cette commande, le `git status` nous marque en vert le ou les fichiers ajoutés grâce à celle-ci. **On dira qu'on suit les fichiers.**

```text
Changes to be committed:
  (use "git restore --staged <file>..." to unstage)
        modified:   src/Controller/ProjetController.php
```

Si on ajoute un fichier par erreur, on peut l'enlever en utilisant `git restore [nom du fichier]`.

Un raccourci utile avec ces deux commandes est `git add .` et `git restore .` qui va ajouter ou enlever tous les fichiers sans exception. Donc, si vous avez un ou plusieurs fichiers `.old` que vous ne voulez pas partager, il n'est pas possible d'utiliser `git add .`.

```bash
git commit -m "Le message de votre commit"
```

Après avoir ajouté tous les fichiers que vous vouliez, on peut passer à la commande `git commit`. Cette commande va ajouter un commit à votre branche suivi du message que vous avez mis :

```text
[develop 3f2a91c] Le message de votre commit
 1 file changed, 12 insertions(+), 3 deletions(-)
```

### 📤 La mise en ligne de votre travail

```bash
git push
```

Cette commande va envoyer tous les commits passés sur le dépôt distant (GitHub/GitLab).

## 🔀 La fusion des travaux communs

### Mise à jour de la branche develop

Pour bien commencer cette étape, il faut impérativement que la branche `develop` en local ait les dernières modifications du remote. Pour ce faire, on peut utiliser la commande suivante :

```bash
git fetch
```

Cette commande va télécharger sans modifier les fichiers du projet en local. Avec la commande :

```bash
git diff develop origin/develop
```

On pourra voir les différences entre le local et le remote. Pour valider le fetch, il faut entrer la commande suivante :

```bash
git merge origin/develop
```

Après la résolution éventuelle de conflits, votre branche `develop` est à jour.

### Début de la fusion

Mettez-vous sur votre branche :

```bash
git checkout [votre branche]
```

Puis faites la commande :

```bash
git merge develop
```

Il y a deux cas : soit le merge se passe sans souci parce que `develop` n'a pas été changé ou parce que les modifications apportées ne créent pas de conflit, soit le merge créera des conflits qu'il faudra résoudre.

!!! caution "⚠️ En cas de conflit"
    Il faudra résoudre les conflits (marqueurs `<<<<<<<`/`=======`/`>>>>>>>` dans les fichiers concernés) puis faire l'étape de commit habituelle (`git add` + `git commit`).

### Fusion de votre branche

Lorsque cela est fait, votre branche a vos modifications et celles de `develop`. Il ne vous reste plus qu'à merger votre branche dans `develop`.

Allez sur la branche `develop` et mergez votre branche :

```bash
git checkout develop
git merge [votre branche]
```

## 🕰️ Commandes pratiques

```bash
git log
```

Cette commande sert à voir l'historique des commits de tout le dépôt.

```text
commit 3f2a91c4e8b5... (HEAD -> develop, origin/develop)
Author: Prenom Nom <email@ma-moulinette.fr>
Date:   Mon Jul 13 10:15:22 2026 +0200

    Le message de votre commit
```

`HEAD` signale votre position courante. Les branches avec le préfixe `origin/` sont les branches distantes ; celles sans préfixe sont vos branches locales.

### 🔤 Accents mal affichés sous Windows (é, à, ê...)

Sous Windows, `git log` (sans redirection) passe par le pager `less.exe` fourni avec Git for Windows. Si la variable d'environnement `LESSCHARSET` n'est pas définie, `less` ne sait pas que le flux est en UTF-8 et affiche chaque caractère accentué sous forme d'octets bruts, par exemple :

```text
Doc: Mise <C3><A0> jour de la documentation
```

Pour vérifier que le problème vient bien du pager, comparez avec :

```powershell
git --no-pager log -5 --format="%s"
```

Si les accents s'affichent correctement sans le pager, les messages de commit sont sains — seul l'affichage est en cause. Correction définitive (variable d'environnement utilisateur, à définir une seule fois) :

```powershell
setx LESSCHARSET utf-8
```

!!! note
    `setx` ne s'applique qu'aux nouvelles fenêtres PowerShell/cmd ouvertes après la commande ; fermez et rouvrez votre terminal pour que le changement prenne effet.

-**-- FIN --**-

[Retour au menu principal](/index.html)
