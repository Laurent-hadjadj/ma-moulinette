# 🛡️ Conformité RGPD

Analyse des données personnelles traitées par Ma-Moulinette, de leur cycle de vie et des écarts à traiter avant une mise en production exposée à des utilisateurs réels.

!!! warning "Analyse technique, pas un avis juridique"
    Cette page est une **analyse technique** fondée sur le code et la configuration de Ma-Moulinette. Ce n'est pas un avis juridique : la qualification (base légale, AIPD, registre) relève du **responsable de traitement** et de son **délégué à la protection des données (DPO)**.
    Les éléments entre crochets `[…]` sont à renseigner par l'organisation qui déploie Ma-Moulinette.

## 1️⃣ Périmètre

| Élément | Valeur |
| --- | --- |
| Traitement | Gestion des comptes et des habilitations d'une application de pilotage qualité/sécurité logicielle (indicateurs SonarQube, OWASP Dependency-Check, ZAP, Actuator Spring Boot) sur un portefeuille de projets, avec traçabilité des actions d'administration et statistiques d'usage |
| Responsable de traitement | `[organisation qui déploie Ma-Moulinette]` |
| DPO | `[contact du DPO]` |
| Personnes concernées | **Utilisateurs** de l'application (développeurs, chefs de projet, RSSI, administrateurs) · **Auteurs d'analyses SonarQube** dont le login apparaît dans l'historique d'activité (pas nécessairement des utilisateurs de Ma-Moulinette elle-même, voir section 2) |
| Sous-traitants | Aucun intégré nativement à l'application. Annuaire LDAP/Active Directory, serveur SonarQube, hébergement : `[à compléter selon le déploiement]` |
| Données sensibles (art. 9) | Aucune |

## 2️⃣ Cartographie des données

| Donnée | Personnes | Où elle vit | Durée | Finalité |
| --- | --- | --- | --- | --- |
| **Identité du compte** (nom, prénom, courriel) | Utilisateurs | Table `utilisateur` | Tant que le compte existe | Authentifier, personnaliser l'affichage, attribuer les actions |
| **Mot de passe** (authentification locale) | Utilisateurs | Colonne `password`, **haché bcrypt (coût 13)**, jamais en clair | Idem | Authentification locale |
| **Identifiants LDAP/Active Directory** | Utilisateurs authentifiés via l'annuaire | **Jamais stockés côté Ma-Moulinette** : bind direct auprès du serveur LDAP à chaque connexion, le mot de passe transite sans être persisté | — | Authentification déléguée à l'annuaire de l'organisme |
| **Rôles et groupe fonctionnel** | Utilisateurs | `utilisateur.roles` (JSON), `groupe_utilisateur`, `groupe_id` | Tant que le compte existe | Habilitations, filtrage du périmètre de projets visible |
| **Avatar** | Utilisateurs | `utilisateur.avatar` — **catalogue d'images prédéfinies** (ex. `animaux/03`), pas de photo personnelle téléversée | Idem | Confort d'affichage |
| **Préférences** (thème, projets/versions favoris) | Utilisateurs | `utilisateur.preference` (JSON), en base, pas un cookie | Idem | Confort d'usage |
| **Session applicative** | Utilisateurs | Cookie de session PHP natif (nom par défaut Symfony) ; fichier sur `var/sessions/` | **Fermeture du navigateur** (`cookie_lifetime: 0`) ; pas de « se souvenir de moi » (`remember_me: false`) | Maintenir la connexion |
| **Journal des modifications de rôles** (`user_role_log`) | Utilisateur modifié (`user_email`) + administrateur auteur (`editor_email`) | Table dédiée | **365 jours** (`app:user-role-log:purge`, voir section 5) | Traçabilité des changements d'habilitation |
| **Auteur d'une analyse SonarQube** (`activity.submitter_login`) | Développeurs ayant lancé une analyse localement, ou compte technique de CI | Table `activity` (brute) et `activity_historique` (résumé), alimentées depuis l'API SonarQube `ce/activity` | **365 jours** (`app:activity:purge`, voir section 5) | Suivi d'activité, statistiques de collecte |
| **Événements de navigation** (user-agent brut, identifiants techniques) | Visiteurs/utilisateurs de pages sensibles (login, logout) | Table `user_agent_event` | **180 jours** (`app:user-agent:purge`, voir section 5) | Détection de bots, statistiques de fréquentation (page Statistiques) |
| **Résultat d'analyse du User-Agent** (navigateur, OS, type d'appareil, bot) | Idem | Table `user_agent_analysis`, calculée depuis `user_agent_event` via Matomo DeviceDetector | Idem | Statistiques d'usage (page Statistiques) |
| **Adresse IP** | Idem | **Jamais stockée en clair** : hachée (SHA-256 + sel applicatif) dans `user_agent_event.ip_hash` avant toute écriture | Idem | Même finalité, sans pouvoir réidentifier directement une adresse IP |

!!! note "✅ Cookie `visitor_id` — corrigé le 16/10/2026"
    Le code (`UserAgentTrackerService::track()`) lit un cookie `visitor_id` et, s'il est absent, en génère un nouveau — avec l'intention documentée dans l'entité `UserAgentEvent` qu'il reste **stable avant/après connexion et malgré la rotation de session**. Jusqu'au 16/10/2026, **aucun code du projet n'écrivait ce cookie dans la réponse HTTP** : un nouvel identifiant était généré à chaque requête, jamais relu. **Corrigé** par `App\EventSubscriber\VisitorIdCookieSubscriber` (écoute `kernel.response`, pose le cookie dès que `UserAgentTrackerService` a signalé la génération d'un nouvel identifiant) — durée 13 mois (plafond CNIL pour un cookie de mesure d'audience), `httpOnly`, `SameSite=Lax`, `Secure` si la requête est en HTTPS. Le cookie suit donc maintenant réellement un même visiteur dans le temps, conformément à l'usage statistique documenté — à mentionner dans la page `/donnees-personnelles`.

### 🍪 Cookies et traceurs

| Cookie / traceur | Rôle | Régime |
| --- | --- | --- |
| Cookie de session (natif Symfony) | Session de l'utilisateur, `cookie_lifetime: 0` (effacé à la fermeture du navigateur) | Strictement nécessaire au service : **exempté** de consentement |
| `browserupdateorg` (bibliothèque `browser-update`, auto-hébergée, pas d'appel à un service tiers) | Mémorise le choix de l'utilisateur sur l'alerte de navigateur obsolète (page de connexion), 7 jours | Préférence d'interface choisie par l'utilisateur : en principe **exemptée** (à confirmer par le DPO) |
| `visitor_id` | Suivi statistique anonymisé (page Statistiques), 13 mois | Mesure d'audience sans finalité publicitaire : exemption à confirmer par le DPO selon les critères CNIL (pas de profilage, pas de croisement avec d'autres traitements) |

!!! note "Pas de traceur tiers"
    Aucune police, bibliothèque ou ressource n'est chargée depuis un domaine tiers (vérifié : pas de Google Fonts, pas d'outil de mesure d'audience, de publicité ou de réseau social embarqué). La page `/donnees-personnelles` déjà en place dans l'application liste ce constat pour `browserupdateorg`, mais reste un gabarit générique de politique cookies — elle ne mentionne pas encore le cookie de session, ni les traitements de traçabilité applicative (journal des rôles, activité, statistiques User-Agent). Voir plan d'action.

## 3️⃣ Principes de l'article 5

| Principe | État | Constat |
| --- | :---: | --- |
| Licéité, loyauté, transparence | 🟡 | Base légale à documenter (section 4) ; page `/donnees-personnelles` en place mais incomplète au regard des traitements réels (section 7) |
| Limitation des finalités | ✅ | Chaque table a un usage identifié (authentification, habilitation, traçabilité, statistiques) ; pas de réutilisation à d'autres fins constatée |
| Minimisation | 🟡 | Mots de passe LDAP jamais stockés, IP toujours hachée, avatar = catalogue (pas de photo), préférences en base plutôt qu'en cookie, `user_agent` brut désormais purgé après 180 jours (`app:user-agent:purge`). Reste : le `user_agent` brut (jusqu'à 1024 caractères) est conservé tel quel pendant cette fenêtre, sans anonymisation précoce |
| Exactitude | ✅ | Identité et rôles modifiables par l'utilisateur/l'administrateur ; `activity.submitter_login` reflète fidèlement l'API SonarQube au moment de la collecte |
| Limitation de la conservation | ✅ | `user_role_log`, `activity`/`activity_historique` et `user_agent_event`/`user_agent_analysis` ont désormais une purge automatique (`app:user-role-log:purge`, `app:activity:purge`, `app:user-agent:purge`, cron quotidien — voir section 13) |
| Intégrité et confidentialité | ✅ | Voir section 8 |
| Responsabilité (accountability) | 🟡 | Registre et AIPD à produire (sections 11 et 12) ; cette page en fournit la matière |

## 4️⃣ Base légale proposée

À valider par le DPO :

| Hypothèse | Argument |
| --- | --- |
| **Mission d'intérêt public** (art. 6.1.e) — si le responsable de traitement est un organisme public | Piloter la qualité et la sécurité logicielle d'un portefeuille de projets de l'organisme |
| **Exécution du contrat de travail / intérêt légitime de l'employeur** (art. 6.1.b/f) | Ma-Moulinette est un outil de travail mis à disposition des développeurs/chefs de projet, sans alternative pour exercer cette partie de leur mission ; mise en balance : les journaux ne portent que l'identité professionnelle, l'action réalisée et l'horodatage |

!!! note "Le consentement n'est pas adapté"
    L'utilisateur n'a pas le choix d'utiliser l'outil s'il doit suivre la qualité de ses projets dans le cadre de son activité professionnelle.

## 5️⃣ Durées de conservation

| Donnée | État actuel | Recommandation |
| --- | --- | --- |
| Session applicative | 0 (fermeture du navigateur) | Déjà conforme |
| Compte utilisateur | Tant que le compte existe (pas de purge automatique des comptes inactifs) | Définir une politique de désactivation après une période d'inactivité (`last_activity_at` est déjà suivi, mais non exploité pour une purge) |
| Journal des rôles (`user_role_log`) | ✅ **365 jours** par défaut (`app:user-role-log:purge`, cron 4h15, réglable via `USER_ROLE_LOG_RETENTION_DAYS`) — export CSV/PDF et suppression manuelle ciblée toujours disponibles (`ROLE_INTERNAL`, page `/admin/journal-roles`) | Déjà conforme (18/10) |
| Activité de collecte (`activity`, `activity_historique`) | ✅ **365 jours** par défaut (`app:activity:purge`, cron 4h30, réglable via `ACTIVITY_RETENTION_DAYS`) | Déjà conforme (18/10) |
| Événements/analyses User-Agent (`user_agent_event`, `user_agent_analysis`) | ✅ **180 jours** par défaut (`app:user-agent:purge`, cron 4h45, réglable via `USER_AGENT_RETENTION_DAYS`) — durée plus courte, donnée purement statistique | Déjà conforme (18/10) |

## 6️⃣ Droits des personnes

| Droit | Application à Ma-Moulinette |
| --- | --- |
| **Accès** (art. 15) | Le DPO/l'administrateur extrait les lignes des journaux où la personne apparaît (`user_email`/`editor_email` dans `user_role_log`, `submitter_login` dans `activity`) — export déjà possible en CSV/PDF depuis `/admin/journal-roles` pour ce premier journal |
| **Rectification** (art. 16) | Possible : l'utilisateur modifie son profil ; l'administrateur corrige nom/prénom/courriel/rôles depuis le back-office (CRUD Utilisateur) |
| **Effacement** (art. 17) | Le compte peut être désactivé/supprimé depuis le back-office. Les journaux (`user_role_log`) disposent déjà d'une suppression manuelle ciblée ; `activity`/`user_agent_event` n'ont pas d'équivalent de suppression ciblée sur demande aujourd'hui (la purge automatique par ancienneté couvre déjà la limitation de conservation générale, voir plan d'action action 8) |
| **Opposition** (art. 21) | À examiner au cas par cas par le DPO |
| **Limitation** (art. 18) | Possible en gelant une éventuelle purge automatique pour les lignes concernées, une fois celle-ci mise en place |
| **Portabilité** (art. 20) | Non applicable en l'absence de consentement ou de contrat au sens RGPD |

## 7️⃣ Information des personnes (art. 13 et 14)

Ma-Moulinette publie déjà la page **`/donnees-personnelles`** (lien dans le pied de page et le plan du site), qui s'appuie sur des paramètres d'environnement déjà en place pour éviter toute donnée d'organisation codée en dur (`CGU_EDITEUR`, `CGU_ADRESSE`, `CGU_SIRET`, `CGU_HEBERGEMENT`, `CGU_URL_SITE`…).

!!! caution "Page existante à compléter"
    En l'état, cette page est un gabarit générique centré sur les cookies (directives 2002/58 et 2009/136) : elle couvre correctement le cookie `browserupdateorg`, mais ne mentionne ni le cookie de session, ni les traitements de traçabilité applicative identifiés dans cette analyse (journal des rôles, historique d'activité, statistiques User-Agent). À compléter avec un paragraphe reprenant la finalité, la base légale, la durée de conservation et le contact du DPO pour ces traitements.

| Public | Vecteur proposé |
| --- | --- |
| Utilisateurs de l'application | Page `/donnees-personnelles` (à compléter) + charte informatique de l'organisme |

## 8️⃣ Sécurité (art. 32)

Mesures en place — détail dans [Gestion de la sécurité](../developpement/securite.md) :

| Mesure | État |
| --- | :--- |
| Mots de passe hachés (bcrypt, coût 13) | ✅ |
| Mots de passe LDAP jamais stockés (bind direct) | ✅ |
| Limitation des tentatives de connexion (`login_throttling`, 3 tentatives / 15 minutes) | ✅ |
| Protection CSRF (`csrf_protection: true`) | ✅ |
| Session sans « se souvenir de moi », effacée à la fermeture du navigateur | ✅ |
| Habilitations par rôle (`role_hierarchy`), périmètre de projets filtré par groupe fonctionnel | ✅ |
| Adresse IP jamais stockée en clair (hachage SHA-256 + sel) | ✅ |
| Journal d'administration des rôles pour toute modification d'habilitation | ✅ |
| En-têtes de sécurité HTTP (CSP, anti-clickjacking…) | ✅ Posés par `App\EventSubscriber\SecurityHeadersSubscriber` (CSP, `X-Frame-Options`, `X-Content-Type-Options`, retrait de `X-Powered-By`) — détail dans [Gestion de la sécurité](../developpement/securite.md) |
| Chiffrement en transit | 🟡 À la charge du reverse proxy HTTPS (obligatoire en production) |
| Chiffrement au repos (base PostgreSQL, journaux) | 🟡 À la charge du volume / de l'hébergeur |
| Sauvegardes | 🟡 Procédure à documenter ; une sauvegarde prolonge la durée de conservation réelle au-delà de la politique affichée |

## 9️⃣ Sous-traitants et transferts hors UE

| Flux | Constat |
| --- | --- |
| Annuaire LDAP/Active Directory | Interne à l'organisme qui déploie Ma-Moulinette ; pas un sous-traitant au sens RGPD si géré en interne |
| Serveur SonarQube | Source de `activity.submitter_login` et des indicateurs qualité ; interne ou hébergé selon le déploiement — `[à préciser, contrat art. 28 si prestataire externe]` |
| Hébergement, reverse proxy | `[à compléter, contrat art. 28 si prestataire]` |
| Polices, bibliothèques JS | ✅ Aucune ressource chargée depuis un domaine tiers (vérifié : pas de Google Fonts, `browser-update` est vendored/auto-hébergé) |
| Services d'analyse, de publicité, de réseaux sociaux | Aucun |

## 🔟 Violation de données (art. 33 et 34)

Scénarios plausibles : compromission de la base PostgreSQL ; accès non autorisé à un compte à privilèges (`ROLE_INTERNAL`) ; fuite des journaux applicatifs.

| Étape | Action |
| --- | --- |
| Détecter | Alerte de l'hébergeur, anomalie dans le journal des rôles (`alerts` déjà collectées sur chaque modification), signalement d'un utilisateur |
| Contenir | Désactiver le ou les comptes concernés, régénérer `APP_SECRET` (invalide toutes les sessions), changer les identifiants LDAP techniques (`LDAP_BIND_DN`/`LDAP_BIND_PASSWORD`) si compromis |
| Évaluer | Les mots de passe sont hachés (locaux) ou jamais stockés (LDAP) : impact limité à la révélation d'identités, de rôles et d'actions d'administration |
| Notifier | DPO immédiatement ; CNIL **sous 72 h** si risque pour les personnes (art. 33) ; personnes concernées si risque élevé (art. 34) |
| Consigner | Registre interne des violations, même sans notification |

## 1️⃣1️⃣ Analyse d'impact (AIPD)

À arbitrer par le DPO. Éléments d'appréciation au regard des critères usuels du G29/CNIL :

| Critère | Ma-Moulinette |
| --- | --- |
| Données sensibles ou hautement personnelles | Non |
| Traitement à grande échelle | Non en principe (volume = effectif de l'organisme ayant accès à l'outil) |
| Croisement de jeux de données | Non : les tables de traçabilité sont indépendantes les unes des autres, pas de profilage croisé |
| Personnes vulnérables (dont salariés) | Oui au sens large (salariés de l'organisme), mais usage limité à la traçabilité d'actions professionnelles |
| Surveillance systématique | Non : traçabilité des actions d'administration et des analyses, pas de suivi continu de l'activité individuelle |
| Usage innovant, exclusion d'un droit ou service | Non |

Au plus un critère est rempli : une AIPD n'apparaît **pas obligatoire**, à confirmer et à consigner dans le registre.

## 1️⃣2️⃣ Fiche de registre (art. 30)

| Rubrique | Contenu |
| --- | --- |
| Nom du traitement | Gestion des comptes, des habilitations et de la traçabilité (Ma-Moulinette) |
| Responsable / DPO | `[…]` / `[…]` |
| Finalités | Authentifier et habiliter les utilisateurs ; tracer les modifications de rôles et l'activité de collecte ; produire des statistiques d'usage |
| Catégories de personnes | Utilisateurs de l'application ; auteurs d'analyses SonarQube |
| Catégories de données | Identité (nom, prénom, courriel), rôles, mot de passe haché, journaux d'administration, données techniques de navigation (User-Agent, IP hachée) |
| Destinataires | Utilisateurs habilités, administrateurs (`ROLE_INTERNAL`), DPO, `[RSSI]`, hébergeur `[…]` |
| Transferts hors UE | Aucun constaté |
| Durées | Compte : tant qu'actif. Journaux : illimitée aujourd'hui, à borner (section 5) |
| Mesures de sécurité | Section 8 |

## 1️⃣3️⃣ Plan d'action

| Priorité | Écart | Action | Où |
| :---: | --- | --- | --- |
| ✅ 1 | `user_role_log` conservé sans limite | **Fait** (18/10) : `app:user-role-log:purge`, retention par défaut 365 jours réglable via `USER_ROLE_LOG_RETENTION_DAYS`, cron quotidien 4h15 | `src/Command/Prod/UserRoleLogPurgeCommand.php` |
| ✅ 2 | `activity`/`activity_historique` conservés sans limite | **Fait** (18/10) : `app:activity:purge`, retention par défaut 365 jours réglable via `ACTIVITY_RETENTION_DAYS`, cron quotidien 4h30 | `src/Command/Prod/ActivityPurgeCommand.php` |
| ✅ 3 | `user_agent_event`/`user_agent_analysis` conservés sans limite | **Fait** (18/10) : `app:user-agent:purge`, retention par défaut 180 jours (donnée purement statistique) réglable via `USER_AGENT_RETENTION_DAYS`, cron quotidien 4h45 | `src/Command/Prod/UserAgentPurgeCommand.php` |
| ✅ 4 | Cookie `visitor_id` jamais réellement déposé | **Fait** : `VisitorIdCookieSubscriber` pose le cookie (13 mois, httpOnly, SameSite=Lax) dès qu'un nouvel identifiant est généré | `src/EventSubscriber/VisitorIdCookieSubscriber.php` |
| 🟡 5 | Page `/donnees-personnelles` incomplète | Ajouter un paragraphe sur le cookie de session et les traitements de traçabilité applicative (finalité, base légale, durée, contact DPO) | `templates/footer/donnees-personnelles.html.twig` |
| ✅ 6 | Aucun en-tête de sécurité HTTP applicatif (CSP, anti-clickjacking) | **Fait** (17/10) : `SecurityHeadersSubscriber` pose CSP/`X-Frame-Options`/`X-Content-Type-Options`, retire `X-Powered-By` | `src/EventSubscriber/SecurityHeadersSubscriber.php` |
| 🟡 7 | Registre et AIPD | Reporter les sections 11 et 12 dans le registre de l'organisme | DPO |
| 🟢 8 | Droit à l'effacement sur `activity`/`user_agent_event` | La purge automatique (actions 1-3) est en place ; prévoir en complément une suppression ciblée sur demande, comme déjà possible pour `user_role_log` | `src/Controller/Admin/` |

## 📌 Pied de page

!!! note
    Ce document fait partie de la documentation de Ma-Moulinette.
    Toute suggestion d'amélioration est bienvenue.

🛟 [`Article 5`](https://www.privacy-regulation.eu/fr/5.htm) - [`Article 6`](https://www.privacy-regulation.eu/fr/6.htm) - [`Article 9`](https://www.privacy-regulation.eu/fr/9.htm) - [`Article 13`](https://www.privacy-regulation.eu/fr/13.htm) - [`Article 14`](https://www.privacy-regulation.eu/fr/14.htm) - [`Article 15`](https://www.privacy-regulation.eu/fr/15.htm) - [`Article 16`](https://www.privacy-regulation.eu/fr/16.htm) - [`Article 17`](https://www.privacy-regulation.eu/fr/17.htm) - [`Article 18`](https://www.privacy-regulation.eu/fr/18.htm) - [`Article 20`](https://www.privacy-regulation.eu/fr/20.htm) - [`Article 21`](https://www.privacy-regulation.eu/fr/21.htm) - [`Article 30`](https://www.privacy-regulation.eu/fr/30.htm) - [`Article 32`](https://www.privacy-regulation.eu/fr/32.htm) - [`Article 33`](https://www.privacy-regulation.eu/fr/33.htm) - [`Article 34`](https://www.privacy-regulation.eu/fr/34.htm)

[Retour au menu principal](/index.html)

> 🔚 -= Fin de la page =-
