<?php
declare(strict_types=1);

namespace App\WebSocket;

use Ratchet\MessageComponentInterface;
use Ratchet\ConnectionInterface;

/**
 * WebSocket Server for Real-time Updates
 * Handles connections and broadcasts messages to clients
 */
class SocialiteWebSocket implements MessageComponentInterface
{
    protected $clients;
    protected $users; // Map user_id => connection

    public function __construct()
    {
        $this->clients = new \SplObjectStorage;
        $this->users = [];
        echo "WebSocket Server initialized\n";
    }

    public function onOpen(ConnectionInterface $conn)
    {
        $this->clients->attach($conn);
        echo "New connection: {$conn->resourceId}\n";
    }

    public function onMessage(ConnectionInterface $from, $msg)
    {
        $data = json_decode($msg, true);
        
        if (!$data) {
            return;
        }

        // Handle authentication/registration
        if (isset($data['action']) && $data['action'] === 'register') {
            $userId = $data['user_id'] ?? null;
            if ($userId) {
                $this->users[$userId] = $from;
                echo "User {$userId} registered for WebSocket updates\n";
                $from->send(json_encode([
                    'type' => 'registered',
                    'user_id' => $userId
                ]));
            }
            return;
        }

        // Handle ping/pong for connection keepalive
        if (isset($data['action']) && $data['action'] === 'ping') {
            $from->send(json_encode(['type' => 'pong']));
            return;
        }

        // Broadcast other messages to all clients
        $numRecv = count($this->clients) - 1;
        echo "Broadcasting message to {$numRecv} clients\n";

        foreach ($this->clients as $client) {
            if ($from !== $client) {
                $client->send($msg);
            }
        }
    }

    public function onClose(ConnectionInterface $conn)
    {
        // Remove from clients
        $this->clients->detach($conn);
        
        // Remove from users map
        foreach ($this->users as $userId => $connection) {
            if ($connection === $conn) {
                unset($this->users[$userId]);
                echo "User {$userId} disconnected\n";
                break;
            }
        }
        
        echo "Connection {$conn->resourceId} has disconnected\n";
    }

    public function onError(ConnectionInterface $conn, \Exception $e)
    {
        echo "An error occurred: {$e->getMessage()}\n";
        $conn->close();
    }

    /**
     * Send message to specific user
     */
    public function sendToUser(int $userId, array $data): void
    {
        if (isset($this->users[$userId])) {
            $this->users[$userId]->send(json_encode($data));
        }
    }

    /**
     * Broadcast to all connected clients
     */
    public function broadcast(array $data): void
    {
        $message = json_encode($data);
        foreach ($this->clients as $client) {
            $client->send($message);
        }
    }
}
