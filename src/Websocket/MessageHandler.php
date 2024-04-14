<?php

namespace App\Websocket;

use Exception;
use Ratchet\ConnectionInterface;
use Ratchet\MessageComponentInterface;
use SplObjectStorage;

class MessageHandler implements MessageComponentInterface
{
    protected $connections;

    public function __construct()
    {
        $this->connections = new SplObjectStorage;
    }

    public function onOpen(ConnectionInterface $conn)
    {
        $this->connections->attach($conn);
        //quand quelqun arrive sur le websocket
        //$conn represente qulqun connecté
    }

    public function onMessage(ConnectionInterface $from, $msg)
    {
        foreach ($this->connections as $connection) {
            if ($connection === $from) {
                continue;
            }
            $connection->send($msg);
        }
        //quand quelqun envoi un message sur le websocket
    }

    public function onClose(ConnectionInterface $conn)
    {
        $this->connections->detach($conn);
        //quand quelqun quitte le websocket
    }

    public function onError(ConnectionInterface $conn, Exception $e)
    {
        $this->connections->detach($conn);
        $conn->close();
    }
}
//web soccet cest propre aux languages web donc sur mobile on fait une api qui prends les données de la websocket