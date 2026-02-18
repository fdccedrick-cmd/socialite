#!/bin/bash

echo "Installing Ratchet WebSocket library..."
cd /var/www/html
composer require cboden/ratchet

echo "✅ Ratchet installed successfully!"
echo ""
echo "Starting WebSocket server on port 8081..."
php bin/websocket-server.php
