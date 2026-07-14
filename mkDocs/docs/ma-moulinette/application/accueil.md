# 🏠 Page d'accueil

La page d'accueil est le point d'entrée de l'application après authentification. Elle affiche l'état des référentiels locaux (projets, profils qualité), permet leur mise à jour, et donne accès aux projets/versions favoris de l'utilisateur.

## 🧭 Haut de page

Le bandeau supérieur (`header.html.twig`, inclus sur **toutes** les pages de l'application, pas seulement l'accueil) affiche :

- le logo (lien vers l'accueil),
- le nom de l'application avec sa version,
- un badge indiquant le type d'environnement (`DEV`/`REC`/`PRD`, défini par la variable `ENVIRONNEMENT`),
- l'adresse courriel de l'utilisateur (ouvre une fenêtre « Informations » : avatar, nom, dates, bascule de réinitialisation du mot de passe), et 2 icônes :

| Icône | Cible | Rôle requis |
| --- | --- | --- |
| Préférences de l'application | `/admin` (back-office EasyAdmin) | `ROLE_UTILISATEUR` (tout le monde) |
| Se déconnecter | `/logout` | `ROLE_UTILISATEUR` |

!!! note "🔐 Une seule icône, plusieurs niveaux d'accès derrière"
    L'icône « Préférences de l'application » est volontairement accessible à tous (`ROLE_UTILISATEUR`) : c'est la porte d'entrée du back-office, dont la page d'accueil affiche ensuite des cartes filtrées par rôle (`ROLE_GESTIONNAIRE`, `ROLE_BATCH`, `ROLE_ACTUATOR`...) — voir [Dashboard back-office](../back-office/dashboard.md#-page-daccueil-cartes). Il n'y a **pas** d'icône « Mes préférences » dans ce bandeau : la page [Préférences](preferences.md) est accessible depuis une carte du back-office (« Réglages personnels ») ou depuis la page « Plan du site » (lien en bas de page).

Depuis la v2.0.0, le back-office donne aussi accès (selon rôle) aux pages [Activité](activite.md), [Statistiques](statistiques.md) et au module [DependencyCheck](../dependency-check/pages.md).

## 📚 Bloc référentiel local

Affiche le nombre de projets et de profils qualité connus localement, comparés au nombre réel sur le serveur SonarQube. Un écart déclenche un message de recommandation de mise à jour, avec le delta (en plus ou en moins). La comparaison ne se fait qu'au-delà d'une fréquence paramétrable (variables `MAJ_PROJET`/`MAJ_PROFIL`, en jours — par défaut recommandation quotidienne pour les projets, mensuelle pour les profils) : si le référentiel a déjà été rafraîchi récemment, aucun signalement n'apparaît même en cas d'écart réel.

La mise à jour manuelle de la liste des projets et des tags nécessite le rôle `ROLE_GESTIONNAIRE`.

## 🏷️ Bloc des tags

!!! note "💡 Astuce"
    Le bloc des tags est affiché **même si l'utilisateur n'a pas le rôle `ROLE_GESTIONNAIRE`**. Il permet de vérifier que les tags sont bien appliqués sur les projets, même pour un simple utilisateur.

Affiche le nombre total de projets du référentiel et le nombre de projets ayant au moins un tag valide.

**Un projet sans tag n'est rattaché à aucun groupe fonctionnel et reste invisible pour les utilisateurs dont l'accès est filtré par groupe** — voir [Groupes](../back-office/groupes.md#-groupe-fonctionnel).

!!! warning Attention
    Le bloc des tags **n'affiche pas les tags eux-mêmes** : il ne s'agit pas d'un inventaire, mais d'une vérification de l'application des tags sur les projets. Pour consulter la liste des tags, voir [Tags](../back-office/tags.md).
    Un projet peut avoir plusieurs tags, mais un tag ne peut pas être appliqué à un projet si le tag n'existe pas dans le référentiel.

## 👁️ Bloc visibilité

!!! note "💡 Astuce"
    Il convient de vérifier que les projets sont bien configurés en visibilité `public` ou `privé`, même pour un simple utilisateur.
    Il est recommandé de ne pas laisser de projet en visibilité `public` car il sera visible par tous les utilisateurs, y compris ceux qui n'ont pas de compte sur l'application.

Affiche le nombre de projets de visibilité `public` et `privé`.

## ⭐ Bloc des favoris

Affiche, au choix de l'utilisateur (paramètre exclusif, pas de mélange possible), soit ses **projets favoris**, soit les **versions favorites** d'un même projet — voir [Préférences](preferences.md). Le nombre de versions affichées est paramétrable (variable `NOMBRE_FAVORI`, 20 par défaut).
Un raccourci permet d'ouvrir directement un projet favori depuis ce bloc.

## ⚠️ Messages d'information

Plusieurs situations peuvent être signalées à l'utilisateur à l'ouverture de la page :

- désynchronisation entre la version de l'application et le schéma de base de données (migration à appliquer) ;
- référentiel de projets ou de profils non à jour par rapport au serveur SonarQube ;
- indisponibilité du serveur SonarQube (erreur 503) ou de l'application elle-même ;
- erreurs HTTP standard lors d'un appel API (400, 403, 404, 503, 504) — voir [Erreurs HTTP](../erreur/http-erreur.md).

-**-- FIN --**-

[Retour au menu principal](/index.html)
