<?php
// Set to development mode to see errors
ini_set('display_errors', 1);
ini_set('error_reporting', E_ALL);

// Simulate login request
$_SERVER['REQUEST_METHOD'] = 'GET';
$_SERVER['REQUEST_URI'] = '/login';
$_SERVER['SERVER_NAME'] = 'localhost';
$_SERVER['SERVER_PORT'] = '80';

try {
    require __DIR__ . '/../vendor/autoload.php';
    
    $app = new App\Application(__DIR__ . '/../config');
    $server = new Cake\Http\Server($app);
    
    $response = $server->run();
    
    echo "Status: " . $response->getStatusCode() . "\n";
    echo "Body:\n";
    echo $response->getBody();
    
} catch (Throwable $e) {
    echo "EXCEPTION: " . get_class($e) . "\n";
    echo "Message: " . $e->getMessage() . "\n";
    echo "File: " . $e->getFile() . ":" . $e->getLine() . "\n\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
}
