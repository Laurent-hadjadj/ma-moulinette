# Mkdocs Ma-Moulinette

Ce projet permet la rédaction de la documentation technique et fonctionelle de l'application Ma-Moulinette sous forme de pages Markdown.
Il utilise **Mkdocs** pour générer un site Web statique à partir des fichiers Markdown et Material for MkDocs pour le thème.

## Prérequis

- [Python 3.x](https://www.python.org/downloads/)
- [Pip](https://pip.pypa.io/en/stable/installation/)
- [Mkdocs](https://www.mkdocs.org/#installation)
- [Material for MkDocs](https://squidfunk.github.io/mkdocs-material/getting-started/)

## Installation

1. Installez les prérequis.
2. Clonez ce dépôt sur votre machine locale.
3. Ouvrez un terminal et naviguez vers le dossier du projet.

## Utilisation

### Environnement de Développement

1. Lancez le serveur de développement avec `python -m mkdocs serve`.
2. Ouvrez votre navigateur Web et accédez à `http://127.0.0.1:8000`.

### Construction du Site

Pour construire le site Web statique, utilisez la commande suivante :

```bash
python -m mkdocs build
```

Le site sera généré dans le dossier `site`. Il suffira d'ajouter les fichiers dans le dossier documentation de l'application.
