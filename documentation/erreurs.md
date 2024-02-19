# Erreurs courantes

![Ma-Moulinette](/documentation/ressources/home-000.jpg)

## Erreur lors de l'impression d'un rapport

Le message ci-dessous est affiché dans la console au moment de la construction du fichier PDF.

```plaintext
Unable to access cssRules property DOMException :
CSSStyleSheet. cssRules getter : Not allowed to access cross-origin stylesheet
```

Cette erreur est due à l'utilisation d'une extension du navigateur. Par exemple, dans Firefox, l'utilisation de l'extension merciApp doit être désactivée.

Pour identifier l'extension en cause, il suffit de désactiver toutes les extensions et de les ajouter l'une après l'autre et vérifier ainsi laquelle peut poser un problème.

-**-- FIN --**-

[Retour au menu principal](/README.md)
