# Architecture des applications Java

![Ma-Moulinette](/documentation/ressources/home-000.jpg)

Il est possible de passer la *moulinette* sur toutes les applications disponibles sur la plateforme SonarQube.

Cependant, certains indicateurs ne seront pas calculés si l'architecture n'est pas conforme à l'organisation qui a été retenue pour les applications Java.

L'architecture pour les applications Java repose sur les principes SOA et de cloisonnement des responsabilités. Les applications sont composées des deux applications JAVA, l'une pour le frontend et la seconde pour le backend.

Les indicateurs de suivi par module (frontend, backend et autre) sont calculés sur la base d'un filtrage du nom du dossier parent.

Le projet Java doit avoir au moins les modules suivants :

Pour l'application frontend :

![Ma-Moulinette](/documentation/ressources/architecture-applicative-presentation.jpg)

Pour l'application frontend :

![Ma-Moulinette](/documentation/ressources/architecture-applicative-metier.jpg)

> Note : Cette architecture peut être adaptée.

Le filtrage est utilisé par la méthode **hotspotDetails()** et la méthode **projetAnomalie()** du controller **ApiProjet**.

Les deux méthodes sont présentes dans la class `Controller\ApiProjetController.php`. Ci-dessous un exemple simple tiré de la méthode hotspotDetails().

```js
$frontend = 0;
    $backend = 0;
    $autre = 0;
    // nom du projet
    $app = explode(":", $mavenKey);

    $status = $hotspot["status"];
    $file = str_replace($mavenKey . ":", "", $hotspot["component"]["key"]);
    $module = explode("/", $file);

    if ($module[0] == "du-presentation") {
      $frontend++;
    }
    if ($module[0] == "rs-presentation") {
      $frontend++;
    }
    if ($module[0] == "rs-metier") {
      $backend++;
    }

    /**
     *  Application Frontend
     */
    if ($module[0] == $app[1] . "-presentation") {
      $frontend++;
    }
    if ($module[0] == $app[1] . "-presentation-commun") {
      $frontend++;
    }
    if ($module[0] == $app[1] . "-presentation-ear") {
      $frontend++;
    }
    if ($module[0] == $app[1] . "-webapp") {
      $frontend++;
    }

    /**
     * Application Backend
     */
    if ($module[0] == $app[1] . "-metier") {
      $backend++;
    }
    if ($module[0] == $app[1] . "-common") {
      $backend++;
    }
    if ($module[0] == $app[1] . "-api") {
      $backend++;
    }
    if ($module[0] == $app[1] . "-dao") {
      $backend++;
    }
    if ($module[0] == $app[1] . "-metier-ear") {
      $backend++;
    }
    if ($module[0] == $app[1] . "-service") {
      $backend++;
    }
    // Application : Legacy
    if ($module[0] == $app[1] . "-serviceweb") {
      $backend++;
    }
    if ($module[0] == $app[1] . "-middleoffice") {
      $backend++;
    }
    // Application : Starter-Kit
    if ($module[0] == $app[1] . "-metier-rest") {
      $backend++;
    }
    // Application : Legacy
    if ($module[0] == $app[1] . "-entite") {
      $backend++;
    }
    // Application : Legacy
    if ($module[0] == $app[1] . "-serviceweb-client") {
      $backend++;
    }

    /**
     * Application Batch et Autres
     */
    if ($module[0] == $app[1] . "-batch") {
      $autre++;
    }
    if ($module[0] == $app[1] . "-batch") {
      $autre++;
    }
    if ($module[0] == $app[1] . "-rdd") {
      $autre++;
    }
```

Ci-dessous la liste des filtres pour une application frontend :

* [x] `presentation-ear`
* [x] `webapp`
* [ ] `presentation`
* [ ] `presentation-commun`

Ci-dessous la liste des filtres pour une application backend :

* [x] `api`
* [x] `common`
* [x] `dao`
* [ ] `service`
* [ ] `metier`
* [ ] `metier-ear`
* [ ] `middleoffice`
* [ ] `serviceweb`
* [ ] `serviceweb-client`
* [ ] `metier-rest`
* [ ] `entite`

Ci-dessous la liste des filtres pour les autres modules backend :

* [ ] `Batchs`
* [ ] `batch`
* [ ] `rdd`

-**-- FIN --**-

[Retour au menu principal](/README.md)
