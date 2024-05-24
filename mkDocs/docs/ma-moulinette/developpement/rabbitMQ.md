# Architecture rabbitMQ

![Ma-Moulinette](/assets/images/home/home-000.jpg)

**RabbitMQ** est un logiciel d'agent de messages open source qui implémente le protocole **Advanced Message Queuing** (AMQP), mais aussi avec des plugins **Streaming Text Oriented Messaging Protocol** (STOMP) et **Message Queuing Telemetry Transport** (MQTT).

Le serveur RabbitMQ est écrit dans le langage de programmation **Erlang**.

## Installation

RabbitMq est disponible pour différentes plateforme. L'environnement de production utilise une image docker. Pour l'environnement de développement, la version windows 64 bit est utilisée.

> Installation de Earlang OTP

Pour utiliser rabbitMQ, il faut tout d'abord installer le langage de programmation Erlang. Le package d'installation pour windows est disponible à l'adresse <https://www.erlang.org/>.

- [x] Le dossier d'installation est **Erlang-OTP**.

> Installation de l'application rabbitMQ.

RabbiMQ est disponible à l'adresse suivante <https://www.rabbitmq.com/docs/download>

- [x] Le dossier d'installation est **rabbitmq-3.13.1**.

## Configuration

Il est nécessaire de définir deux variables avant de démarrer le serveur.

- [x] dans le script `/sbin/rabbitmq-default.bat` ajoutez la variable **ERLANG_HOME** avec le chemin du dossier contannat Earlang.
- [x] dans le script `/sbin/rabbitmq-default.bat` ajouter la variavle **RABBITMQ_BASE** avec le chemin du dossier rabbitMQ.

Par exmeple pour Earlang OTP :

```properties
set ERLANG_HOME=c:\env\ma-moulinette\Erlang-OTP
```

Par exemple pour l'application rabbitMQ :

```properties
set RABBITMQ_BASE=c:\env\ma-moulinette\ma-moulinette\RabbitMQ
```

Note : le dossier contenant les log et la base de données est présente dans le dossier contenant les sources de l'application **ma-moulinette**, dans le dossier **RabbitMQ**.

## Activation des plugins

> Installation de l'interface de management.

Pour activer l'interface web de gestion, il suffit de lancer la commande :

```plaintext
>rabbitmq-plugins enable rabbitmq-management

Enabling plugins on node rabbit@xxxxxxxxxxxx:
rabbitmq_management
The following plugins have been configured:
  rabbitmq_management
  rabbitmq_management_agent
  rabbitmq_web_dispatch
Applying plugin configuration to rabbit@xxxxxxxxxxxx...
The following plugins have been enabled:
  rabbitmq_management
  rabbitmq_management_agent
  rabbitmq_web_dispatch

set 3 plugins.
Offline change; changes will take effect at broker restart.
```

Pour lancer l'interface de gestion, entrez l'URL suivante dans un navigateur : <localhost:15672>

Le login par défaut est **guest**, le mot de passe est **guest**.

![rabbitMQ](/assets/images/rabbitmq/rabbitMQ-001.jpg)

## Démarrage du serveur

Le démarrage du serveur se fait en lancant la commande suivante :

```plaintext
> rabbitmq-server.bat

2024-03-30 21:51:58.488000+01:00 [notice] <0.44.0> Application syslog exited with reason: stopped
2024-03-30 21:51:58.504000+01:00 [notice] <0.248.0> Logging: switching to configured handler(s); following messages may not be visible in this log output

  ##  ##      RabbitMQ 3.13.1
  ##  ##
  ##########  Copyright (c) 2007-2024 Broadcom Inc and/or its subsidiaries
  ######  ##
  ##########  Licensed under the MPL 2.0. Website: https://rabbitmq.com

  Erlang:      26.2.3 [jit]
  TLS Library: OpenSSL - OpenSSL 3.1.0 14 Mar 2023
  Release series support status: supported

  Doc guides:  https://www.rabbitmq.com/docs/documentation
  Support:     https://www.rabbitmq.com/docs/contact
  Tutorials:   https://www.rabbitmq.com/tutorials
  Monitoring:  https://www.rabbitmq.com/docs/monitoring

  Logs: <stdout>
        c:/env/ma-moulinette/ma-moulinette/RabbitMQ/log/rabbit@xxxxxxxxxx.log

  Config file(s): (none)

  Starting broker... completed with 3 plugins.
```

## Paramétrage PHP

Il faut décommenter l'extension **sockets** dans le fichier **php.ini**.

## Stratégie

- [x] Point-à-point entre le publisher et le broker pour les échanges d'information.
- [X] Demande-réponse pour les traitements inter-application.

## Commandes utiles

> Création d'un utilisateur

`rabbitmqctl add_user <login> <mot_de_passe>`

- [x] rabbitmqctl add_user admin <mot_de_passe>

- rabbitmqctl add_user full_access s3crEt
- rabbitmqctl set_user_tags full_access administrator

> Donner les droits d'administration

`rabbitmqctl.bat set_user_tags <utilisateur> administrator`

> Création des vhosts

Pour créer un espace de communication.

`rabbitmqctl.bat add_vhost <vhost>`

- [x] rabbitmqctl.bat add_vhost information
- [x] rabbitmqctl.bat add_vhost traitement

> Changer les droits d'un utilisateur sur un vhost

Les droits possibles sont :

- [x] configure
- [x] write
- [x] read

`rabbitmqctl.bat set_permissions –p <vhost> <utilisateur> ".*" ".*" ".*"`

> Afficher les permissions

`rabbitmqctl.bat list_permissions`

> Supprimer les permissions pour un utilisateurs

`rabbitmqctl.bat clear_permissions -p <vhost> <utilisateur>`

-**-- FIN --**-

[Retour au menu principal](/index.html)
