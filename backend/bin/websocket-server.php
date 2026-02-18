#!/usr/bin/env php
<?php
declare(strict_types=1);

/**
 * WebSocket Server Entry Point
 * Run this script to start the WebSocket server
 * 
 * Usage: php bin/websocket-server.php
 */

require dirname(__DIR__) . '/vendor/autoload.php';

use Ratchet\Server\IoServer;
use Ratchet\Http\HttpServer;
use Ratchet\WebSocket\WsServer;
use App\WebSocket\SocialiteWebSocket;

$port = 8081;

echo "Starting WebSocket server on port {$port}...\n";
echo "WebSocket URL: ws://localhost:{$port}\n";
echo "Press Ctrl+C to stop\n\n";

$server = IoServer::factory(
    new HttpServer(
        new WsServer(
            new SocialiteWebSocket()
        )
    ),
    $port
);

$server->run();
