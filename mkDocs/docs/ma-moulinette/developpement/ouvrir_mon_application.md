# 🚪 Ouvrir l'application

En développement, l'application est accessible par défaut à l'adresse `http://localhost:8000` (serveur `symfony-cli`) ou via les conteneurs Docker décrits dans [Environnement d'exécution](../architecture/architecture-technique.md#-environnement-dexécution).

Il est possible de configurer un proxy pour exposer l'application sur un nom de domaine local dédié plutôt que sur `localhost`.

!!! caution "⚠️ HTTPS en local"
    Servir l'application en HTTPS depuis un serveur local n'est recommandé que si vous maîtrisez la configuration du reverse proxy (Nginx/Traefik) qui la sert — voir [Architecture technique](../architecture/architecture-technique.md).

Une fois connecté (voir [Authentification](../authentification/authentification.md)), l'utilisateur arrive sur la page [Accueil](../application/accueil.md).

-**-- FIN --**-

[Retour au menu principal](/index.html)
