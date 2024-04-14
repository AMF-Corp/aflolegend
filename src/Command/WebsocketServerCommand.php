<?php

namespace App\Command;

use Ratchet\Http\HttpServer;
use Ratchet\Server\IoServer;
use Ratchet\WebSocket\WsServer;
use App\Websocket\MessageHandler;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class WebsocketServerCommand extends Command
{
    protected static $defaultName = "run:websocket-server"; //nom de la commande quon choisi 

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $port = 3001;
        //mettre nimporte quel port qui n'est pas utilisé
        $output->writeln("Starting server on port " . $port); //juste pour avoir un retour pour les dev
        $server = IoServer::factory( //demarrer un serveur
            new HttpServer(
                new WsServer( //serveur Web socket
                    new MessageHandler()
                )
            ),
            $port
        );
        $server->run();
        return 0;
        //messages retour de la commande 
        //0 et positifs cest ok 
        //-1 et negatifs cest pas bon 
    }
}
