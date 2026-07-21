# ⚙️ Préférences utilisateur

Consultation des informations personnelles et personnalisation du suivi de projets. Aucun rôle spécifique requis (`ROLE_UTILISATEUR`).

!!! note "🔗 Accès"
    Cette page n'a pas d'icône dédiée dans le bandeau commun (voir [Accueil](accueil.md#-haut-de-page)) : elle est accessible depuis la carte « Réglages personnels » de la page d'accueil du back-office (`/admin`, voir [Dashboard back-office](../back-office/dashboard.md#-page-daccueil-cartes)), ou depuis la page « Plan du site » (lien en bas de page).

## 👤 Informations personnelles

Affiche en lecture seule : avatar, nom/prénom, adresse courriel, rôles, groupe utilisateur (organisation de rattachement, un seul) et groupes fonctionnels (périmètre applicatif, un ou plusieurs).

!!! note "✅ ajout du groupe utilisateur"
    Cette page n'affichait auparavant que les groupes fonctionnels. Le groupe utilisateur (`Utilisateur::getGroupeUtilisateur()`, une seule valeur possible) est désormais affiché en complément, avec `Aucun` comme valeur par défaut si l'utilisateur n'en a pas.

## 🌱 Lien vers Actuator

Un bouton « Accéder à l'inventaire Actuator » est affiché sur cette page uniquement pour les utilisateurs disposant du rôle `ROLE_ACTUATOR` — voir [Actuator](actuator.md). La carte « Actuator » de la page d'accueil du back-office (même rôle requis) offre un second point d'entrée équivalent.

## ⭐ Tableau des préférences (3 interrupteurs)

| Option affichée | Clé réelle | Description affichée |
| --- | --- | --- |
| **Projet** | `suivi_projet` | Liste des projets à suivre |
| **Favori** | `favori_projet` | Liste des projets favoris |
| **Version** | `favori_version` | Liste des versions favorites |

Chaque interrupteur ouvre une modale listant les éléments concernés, avec un bouton de suppression par ligne pour les favoris et les versions — ces deux suppressions déclenchent bien un appel réseau.

!!! note "✅ clés désormais alignées avec le reste de l'application"
    Ces interrupteurs et leurs modales lisaient/écrivaient auparavant des clés `projet`/`favori`/`version` distinctes de celles utilisées ailleurs (bouton « cœur » de la page [Projet](projet.md), bloc favoris de l'[Accueil](accueil.md#-bloc-des-favoris), [Suivi](suivi.md)), ce qui rendait ces 3 réglages sans effet réel. `PreferenceController`, le JavaScript de la page et la suppression de version favorite (dont l'appel réseau était commenté) ont été corrigés pour utiliser les vraies clés `suivi_projet`/`favori_projet`/`favori_version` — les 3 interrupteurs et leurs modales pilotent maintenant bien l'état affiché ailleurs dans l'application.

La page « Ajouter une préférence » (orpheline, non routée, bouton « Valider » non fonctionnel) a été supprimée du code (template + JS).

Les 3 modales (Favoris/Projets/Versions) utilisent désormais un élément `<dialog>` avec gestion du focus à l'ouverture/fermeture (retour sur le bouton déclencheur), pour un fonctionnement correct au clavier et avec un lecteur d'écran.

## 💾 Enregistrement

Les préférences sont persistées en base (table `utilisateur`, colonne `preference` au format JSON). Chaque bascule d'interrupteur déclenche un enregistrement immédiat via une requête asynchrone.

## 📚 Pour aller plus loin

- [Projet](projet.md) : bouton « cœur » pour gérer un favori-projet.
- [Accueil](accueil.md#-bloc-des-favoris) : affichage des favoris.
- [Suivi](suivi.md) : gestion des favoris de version et de la version de référence.
- [Actuator](actuator.md) : accessible depuis cette page pour les utilisateurs `ROLE_ACTUATOR`.

-**-- FIN --**-

[Retour au menu principal](/index.html)
