# Architecture rabbitMQ

![Ma-Moulinette](/assets/images/home/home-000.jpg)

**RabbitMQ** est un logiciel d'agent de messages open source qui implémente le protocole **Advanced Message Queuing** (AMQP), mais aussi avec des plugins **Streaming Text Oriented Messaging Protocol** (STOMP) et **Message Queuing Telemetry Transport** (MQTT).

Le serveur RabbitMQ est écrit dans le langage de programmation **Erlang**.

## Installation

RabbitMq est disponible pour différentes plateforme. L'environnement de production utilise une image docker. Pour l'environnement de développement, la version windows 64 bit est utilisée.

> Installation de Earlang OTP

Pour utiliser rabbitMQ, il faut tout d'abord installer le langage de programmation **Erlang**. Le package d'installation pour windows est disponible à l'adresse <https://www.erlang.org/>.

- [x] Le dossier d'installation est **Erlang-OTP**.

> Installation de l'application rabbitMQ.

RabbiMQ est disponible à l'adresse suivante <https://www.rabbitmq.com/docs/download>

- [x] Le dossier d'installation est **rabbitmq-3.13.1**.

## Configuration

Il est nécessaire de définir deux variables avant de démarrer le serveur.

- [x] dans le script `/sbin/rabbitmq-default.bat` ajoutez la variable **ERLANG_HOME** avec le chemin du dossier contenant Earlang.
- [x] dans le script `/sbin/rabbitmq-default.bat` ajouter la variable **RABBITMQ_BASE** avec le chemin du dossier rabbitMQ.

Par exemple pour Earlang OTP :

```properties
set ERLANG_HOME=c:\environnement\0_tools\Erlang-OTP
```

Par exemple pour l'application rabbitMQ :

```properties
set RABBITMQ_BASE=c:\environnement\ma-moulinette\RabbitMQ
```

**Note** : le dossier contenant les log et la base de données est présente dans le dossier contenant les sources de l'application **ma-moulinette**, dans le dossier **RabbitMQ**.

> Attention : il faudra vérifier que le fichier **erl.ini** présents dans le dossier c:\environnement\0_toolz\Erlang-OTP\bin\ et c:\environnement\0_toolz\Erlang-OTP\erts-14.2.3\bin\ est bien à jour au niveau des 'path'.

## Activation des plugins

> Installation de l'interface web de management.

Pour activer l'interface web de gestion, il suffit de lancer la commande :

```bash
rabbitmq-plugins enable rabbitmq-management
```

```plaintext
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

> Installation du plugin RabbitMQ Web STOMP.

1 - Ce plugin permet de gérer les connexions WebSocket et donc de recevoir des webhooks. Pour l'installer, vous pouvez utiliser la commande suivante dans le terminal :

```bash
rabbitmq-plugins enable rabbitmq_web_stomp
```

Le résultat est le suivant :

```plaintext
".. __  __             __  __             _              _   _       "
"  |  \/  | __ _      |  \/  | ___  _   _| (_)_ __   ___| |_| |_ ___ "
"  | |\/| |/ _` |_____| |\/| |/ _ \| | | | | | '_ \ / _ \ __| __/ _ \"
"  | |  | | (_| |_____| |  | | (_) | |_| | | | | | |  __/ |_| ||  __/"
"  |_|  |_|\__,_|     |_|  |_|\___/ \__,_|_|_|_| |_|\___|\__|\__\___|"

   Laurent HADJADJ
   https://github.com/Laurent-hadjadj/ma-moulinette
   © 2024 - CC BY-SA-NC 4.0


Lecteur : c:
ERLang : c:\environnement\0_toolz\Erlang-OTP
rabbitMQ : c:\environnement\ma-moulinette\RabbitMQ
Enabling plugins on node rabbit@xxxxxxx:
rabbitmq_web_stomp
The following plugins have been configured:
  rabbitmq_management
  rabbitmq_management_agent
  rabbitmq_stomp
  rabbitmq_web_dispatch
  rabbitmq_web_stomp
Applying plugin configuration to rabbit@Tuf-Gaming...
The following plugins have been enabled:
  rabbitmq_stomp
  rabbitmq_web_stomp

set 5 plugins.
Offline change; changes will take effect at broker restart.
```

2 - Configurer le serveur RabbitMQ pour accepter les connexions WebSocket : pour cela, vous devez ajouter une nouvelle entrée dans le fichier de configuration de RabbitMQ (généralement situé dans /etc/rabbitmq/rabbitmq.conf) pour activer le plugin Web STOMP et définir le port d'écoute.

Voici un exemple de configuration :

```plaintext
listeners.tcp.default = 5672
listeners.ssl.default = 5671
listeners.web.default = 15672
web_stomp.default_user = guest
web_stomp.default_pass = guest
```

3 - Créer une file d'attente pour recevoir les webhooks : vous pouvez créer une file d'attente en utilisant l'interface de gestion de RabbitMQ. La file d'attente doit être liée à un échange de type "fanout" pour recevoir tous les messages envoyés par les webhooks.

4 - Configurer les webhooks pour envoyer des messages à la file d'attente : vous devez configurer les webhooks pour qu'ils envoient des messages à l'échange de type "fanout" que vous avez créé à l'étape précédente. Les messages doivent être au format JSON et contenir les données que vous souhaitez traiter.

## Démarrage du serveur

Le démarrage du serveur se fait en lançant la commande suivante :

```bash
rabbitmq-server.bat
```

```plaintext
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
        c:/environnement/ma-moulinette/RabbitMQ/log/rabbit@xxxxxxxxxx.log

  Config file(s): (none)

  Starting broker... completed with 5 plugins.
```

## Paramétrage PHP

Il faut décommenter l'extension **sockets** dans le fichier **php.ini**.

## Stratégie

- [x] Point-à-point entre le publisher et le broker pour les échanges d'information.
- [X] Demande-réponse pour les traitements inter-application.

## Liste des queues

Trois files d'attente sont utilisées.

- [x] **traitement_manuel_queue** est une file d'attente créée automatiquement ;
- [x] **traitement_automatique_queue** est une file d'attente créée automatiquement ;
- [ ] **webhooks_queue** est une file d'attente à créer manuellement depuis l'interface graphique ;

![rabbitMQ](/assets/images/rabbitmq/rabitMQ-002.jpg)

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

- [x] rabbitmqctl.bat add_vhost ma_moulinette

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

> Ajouter la file d'attente webhooks de type fanout

```bash
rabbitmqadmin declare queue name=webhooks durable=true auto_delete=false arguments='{"x-dead-letter-exchange":"","x-dead-letter-routing-key":"","x-message-ttl":0,"x-expires":0,"x-max-length":0,"x-max-length-bytes":0,"x-overflow":"reject-publish"}' --vhost=/
```

Cette commande utilise l'outil **rabbitmqadmin** pour déclarer une file d'attente nommée "webhooks" avec les options suivantes :

- durable=true : la file d'attente est persistante et survit à un redémarrage du serveur RabbitMQ.
- auto_delete=false : la file d'attente n'est pas supprimée automatiquement lorsque tous les consommateurs se déconnectent.
- arguments : cette option permet de définir des arguments supplémentaires pour la file d'attente.
- --vhost=/ : cette option permet de spécifier le vhost sur lequel la file d'attente doit être créée. Dans cet exemple, la file d'attente est créée sur le vhost par défaut de RabbitMQ (/).

Une fois la file d'attente créée, vous pouvez la lier à un échange de type "fanout" pour recevoir tous les messages envoyés par les webhooks. Pour cela, vous pouvez utiliser la commande suivante :

```bash
rabbitmqadmin declare exchange name=webhooks type=fanout --vhost=/
rabbitmqadmin declare binding source=webhooks destination_type=queue destination=webhooks routing_key= --vhost=/
```

Si vous utilisez RabbitMQ sur Windows et que vous n'avez pas la commande **rabbitmqadmin**, vous pouvez utiliser l'interface de gestion web de RabbitMQ pour créer une file d'attente et la lier à un échange.

Pour accéder à l'interface de gestion web de RabbitMQ, ouvrez un navigateur web et accédez à l'URL suivante :

```plaintext
http://localhost:15672
```

Connectez-vous avec les identifiants d'un utilisateur ayant les autorisations nécessaires pour gérer les files d'attente et les échanges (par défaut, l'utilisateur "guest" a des droits limités et ne peut pas gérer les files d'attente et les échanges).

Une fois connecté, cliquez sur l'onglet "Queues and streams", puis cliquez sur le bouton "Add a new queue" en bas à gauche de la page.

![rabbitMQ](/assets/images/rabbitmq/ajout-queue-webhooks-001.jpg)

Remplissez les champs suivants :

- Type : choisissez "classic" ;
- Name : nom de la file d'attente ("webhooks_queue") ;
- Durability : sélectionnez "Durable" pour que la file d'attente survive à un redémarrage du serveur RabbitMQ ;
- Auto-delete : sélectionnez "NO" pour que la file d'attente ne soit pas supprimée automatiquement lorsque tous les consommateurs se déconnectent ;
- Arguments : laissez ce champ vide pour l'instant

![rabbitMQ](/assets/images/rabbitmq/ajout-queue-webhooks-002.jpg)

Cliquez sur le bouton "Add queue" pour créer la file d'attente.

![rabbitMQ](/assets/images/rabbitmq/ajout-queue-webhooks-003.jpg)

Ensuite, cliquez sur l'onglet "Exchanges". Remplissez les champs suivants :

- Name : nom de l'échange ("webhooks_exchange") ;
- Type : sélectionnez "fanout" pour que tous les messages envoyés à l'échange soient distribués à toutes les files d'attente liées ;
- Durability : sélectionnez "Durable" pour que l'échange survive à un redémarrage du serveur RabbitMQ ;
- Auto-delete : sélectionnez "No" pour que l'échange ne soit pas supprimé automatiquement lorsque tous les consommateurs se déconnectent ;
- Internal : laissez cette option à "No" ;
- Arguments : laissez ce champ vide pour l'instant ;

![rabbitMQ](/assets/images/rabbitmq/ajout-queue-webhooks-004.jpg)

Cliquez sur le bouton "Add exchange" pour créer l'échange.

![rabbitMQ](/assets/images/rabbitmq/ajout-queue-webhooks-005.jpg)

Enfin, cliquez sur le nom de l'échange qui vient d'être créé ()"webhooks_queue"), puis sur le menu "Bindings". Remplissez les champs suivants :

- Destination type : sélectionnez "To Queue"
- Destination : sélectionnez la file d'attente que vous venez de créer ("webhooks_queue")
- Routing key : laissez ce champ vide pour que tous les messages envoyés à l'échange soient distribués à la file d'attente ;
- Arguments : laissez ce champ vide pour l'instant ;

![rabbitMQ](/assets/images/rabbitmq/ajout-queue-webhooks-006.jpg)

Cliquez sur le bouton "Bind" pour lier la file d'attente à l'échange.

![rabbitMQ](/assets/images/rabbitmq/ajout-queue-webhooks-007.jpg)

Vous avez maintenant créé une file d'attente nommée "webhooks_queue" avec un type "fanout" sur le vhost par défaut de RabbitMQ, et lié cette file d'attente à un échange nommé "webhooks". Tous les messages envoyés à l'échange "webhooks_exchange" seront alors distribués à la file d'attente "webhooks_queue".


-**-- FIN --**-

[Retour au menu principal](/index.html)
