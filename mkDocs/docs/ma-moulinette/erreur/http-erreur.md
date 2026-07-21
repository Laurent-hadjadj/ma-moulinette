# 🚨 Erreurs HTTP

Les pages d'erreur HTTP (`templates/bundles/TwigBundle/Exception/error*.html.twig`) surchargent les pages par défaut de Symfony pour les codes 400, 401, 403, 404, 500, 503 et 504, plus un template générique `error.html.twig` (fallback pour tout autre code non couvert individuellement), avec un habillage graphique commun (clin d'œil « Guru Meditation », en référence à l'écran de plantage historique d'Amiga, avec un code hexadécimal généré aléatoirement — purement cosmétique, sans lien avec l'erreur réelle).

| Code | Signification | Cause typique |
| --- | --- | --- |
| 400 | Requête incorrecte | Paramètres de requête invalides ou manquants |
| 401 | Non authentifié | Session expirée ou absente |
| 403 | Accès refusé | Rôle insuffisant pour la ressource demandée |
| 404 | Ressource introuvable | Projet/page inexistant(e), ou route inconnue |
| 500 | Erreur serveur | Exception non gérée côté application |
| 503 | Service indisponible | Serveur SonarQube injoignable, ou endpoint volontairement désactivé (ex. token non configuré) |
| 504 | Passerelle expirée | Le serveur SonarQube ou un service dépendant ne répond pas dans les temps |

## 🔒 Journalisation

`App\EventListener\ExceptionListener` journalise systématiquement les exceptions non-HTTP ou de statut ≥ 500 (fichier, ligne, trace), avec un **masquage automatique** des valeurs sensibles détectées dans le message ou la trace (`password=`, `secret=`, `token=`, `key=`, remplacées par `***MASKED***`) avant écriture dans les logs.
Les erreurs HTTP 4xx classiques (400/401/403/404) ne génèrent pas cette journalisation détaillée — elles sont considérées comme des cas d'usage attendus, pas des anomalies applicatives.

-**-- FIN --**-

[Retour au menu principal](/index.html)
