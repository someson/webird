<?php
namespace Webird\Modules\Cli\Tasks;

use ZMQ;
use PDO;
use React\ZMQ\Context as ZMQContext;
use React\EventLoop\Factory as EventLoopFactory;
use Ratchet\Server\IoServer;
use Ratchet\Http\HttpServer;
use Ratchet\WebSocket\WsServer;
use Ratchet\Session\SessionProvider;
use Symfony\Component\HttpFoundation\Session\Storage\Handler;
use Webird\CLI\Task;
use Webird\Modules\Cli\Chat;

/**
 * Task for websocket
 *
 */
class ServiceTask extends Task
{
    /**
     *
     */
    public function mainAction(array $params)
    {
        echo "The default action inside of the ", CURRENT_TASK, " task is not configured\n";
    }

    /**
     *
     */
    public function websocketListenAction($argv)
    {
        $config = $this->di->getConfig();

        $params = $this->parseArgs($argv, [
            'title' => 'Start the websocket listener (start this through the server command).',
            'args' => [
                'required' => [],
                'optional' => [],
            ],
            'opts' => [
                'p|wsport:'    => "websockets listen on port (default is {$config->app->wsPort}).",
                'z|zmqport:' => "zmq listen on port (default is {$config->app->zmqPort}).",
            ]
        ]);

        // $this->ensureRunningAsWebUser();
        $opts = $params['opts'];
        $config = $this->config;

        $wsPort = (isset($opts['wsport'])) ? $opts['wsport'] : $config->app->wsPort;
        $zmqPort = (isset($opts['zmqport'])) ? $opts['zmqport'] : $config->app->zmqPort;

        $loop = EventLoopFactory::create();
        $chat = new Chat();
        $chat->setDI($this->getDI());


        // Listen for the web server to make a ZeroMQ push after an ajax request
        // $context = new ZMQContext($loop);
        // $pull = $context->getSocket(ZMQ::SOCKET_PULL);
        // $pull->bind("tcp://127.0.0.1:${zmqPort}"); // Binding to 127.0.0.1 means the only client that can connect is itself
        // $pull->on('message', [$chat, 'onUserJoin']);

        $wsServer = new WsServer($chat);

        $ioServer = IoServer::factory(
            new HttpServer($wsServer),
            $wsPort
        );

        echo "websocket listening on port $wsPort in " . ENV . " mode\n";

        $ioServer->run();
    }

}
