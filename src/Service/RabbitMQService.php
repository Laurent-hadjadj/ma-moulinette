<?php

/*
 *  Ma-Moulinette
 *  --------------
 *  Copyright (c) 2021-2024.
 *  Laurent HADJADJ <laurent_h@me.com>.
 *  Licensed Creative Common  CC-BY-NC-SA 4.0.
 *  ---
 *  Vous pouvez obtenir une copie de la licence à l'adresse suivante :
 *  http://creativecommons.org/licenses/by-nc-sa/4.0/
 */

namespace App\Service;

/** AMQP */
use PhpAmqpLib\Connection\AMQPStreamConnection;
use PhpAmqpLib\Exception\AMQPProtocolChannelException;
use PhpAmqpLib\Exception\AMQPChannelClosedException;
use PhpAmqpLib\Exception\AMQPConnectionClosedException;
use PhpAmqpLib\Message\AMQPMessage;

/**
 * [Description RabbitMQTools]
 */
class RabbitMQService
{
    private $connection;
    private $channel;
    private $host;
    private $port;
    private $user;
    private $password;
    private $vhost;

    public function __construct(string $host, int $port, string $user, string $password, string $vhost)
    {
        $this->host = $host;
        $this->port = $port;
        $this->user = $user;
        $this->password = $password;
        $this->vhost = $vhost;
        $this->connect();
    }

    /**
     * [Description for connect]
     *
     * @return void
     *
     * Created at: 17/06/2024 21:53:15 (Europe/Paris)
     * @author     Laurent HADJADJ <laurent_h@me.com>
     * @copyright  Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    private function connect(): void
    {
        $this->connection = new AMQPStreamConnection($this->host, $this->port, $this->user, $this->password, $this->vhost);
        $this->channel = $this->connection->channel();
    }

    /**
     * [Description for ensureChannelOpen]
     *
     * @return void
     *
     * Created at: 17/06/2024 21:53:22 (Europe/Paris)
     * @author     Laurent HADJADJ <laurent_h@me.com>
     * @copyright  Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    private function ensureChannelOpen(): void
    {
        if (!$this->connection->isConnected()) {
            throw new  AMQPConnectionClosedException('AMQP connection is not open.');
        }
        if ($this->channel === null || !$this->channel->is_open()) {
            $this->connect($this->host, $this->port, $this->user, $this->password, $this->vhost); // reconnecter si le canal est fermé
        }
        if (!$this->channel->is_open()) {
            throw new AMQPChannelClosedException('AMQP channel is closed.');
        }
    }

    /**
     * [Description for queueExists]
     *
     * @param string $queueName
     *
     * @return bool
     *
     * Created at: 14/06/2024 14:18:45 (Europe/Paris)
     * @author     Laurent HADJADJ <laurent_h@me.com>
     * @copyright  Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    public function queueExists(string $queueName): bool
    {
        try {
            $this->ensureChannelOpen();
            $this->channel->queue_declare($queueName, true, true, false, false, false);
            return true;
        } catch (AMQPProtocolChannelException $e) {
            if ($e->getCode() === 404) {
                return false;
            } else {
                throw $e;
            }
        }
    }

    /**
     * [Description for createQueue]
     *
     * @param string $queueName
     *
     * @return void
     *
     * Created at: 14/06/2024 14:07:31 (Europe/Paris)
     * @author     Laurent HADJADJ <laurent_h@me.com>
     * @copyright  Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    public function createQueue(string $queueName): void
    {

        try {
            $this->ensureChannelOpen();
            $this->channel->queue_declare(
                $queueName,
                false, // passive
                true,  // durable
                false, // exclusive
                false  // auto_delete
            );
        } catch (AMQPProtocolChannelException $e) {
            throw new \RuntimeException('Failed to declare queue: ' . $queueName, 0, $e);
        }
    }

    /**
     * [Description for createQueueIfNotExists]
     *
     * @param string $queueName
     *
     * @return void
     *
     * Created at: 14/06/2024 14:21:53 (Europe/Paris)
     * @author     Laurent HADJADJ <laurent_h@me.com>
     * @copyright  Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    public function createQueueIfNotExists(string $queueName): void
    {
        if (!$this->queueExists($queueName)) {
            $this->createQueue($queueName);
        }
    }

    /**
     * [Description for close]
     *
     * @return void
     *
     * Created at: 14/06/2024 14:07:41 (Europe/Paris)
     * @author     Laurent HADJADJ <laurent_h@me.com>
     * @copyright  Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    public function close(): void
    {
        if ($this->channel !== null) {
            $this->channel->close();
            $this->channel = null; // réinitialiser le canal à null
        }
        if ($this->connection !== null) {
            $this->connection->close();
        }
    }

    /**
     * [Description for getMessageCount]
     *
     * @param string $queueName
     *
     * @return int
     *
     * Created at: 14/06/2024 14:07:10 (Europe/Paris)
     * @author     Laurent HADJADJ <laurent_h@me.com>
     * @copyright  Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    public function getMessageCount(string $queueName): int
    {
        /** On initialise la connexion */
        $channel = $this->connection->channel();
        list($queue, $messageCount, $consumerCount) = $channel->queue_declare($queueName, true);
        $this->close();
        return $messageCount;
    }

    /**
     * [Description for sendMessage]
     *
     * @param string $queueName
     * @param string $messageBody
     *
     * @return [type]
     *
     * Created at: 14/06/2024 19:16:59 (Europe/Paris)
     * @author     Laurent HADJADJ <laurent_h@me.com>
     * @copyright  Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    public function sendMessage(string $queueName, string $messageBody)
    {
        $msg = new AMQPMessage($messageBody, ['delivery_mode' => AMQPMessage::DELIVERY_MODE_PERSISTENT]);
        $this->channel->basic_publish($msg, '', $queueName);
    }

}
