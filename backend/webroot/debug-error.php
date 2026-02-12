<?php
// Enable all error reporting
error_reporting(E_ALL);
ini_set('display_errors', '1');
ini_set('display_startup_errors', '1');

try {
    require __DIR__ . '/../vendor/autoload.php';
    
    echo "Autoload OK\n";
    
    $app = new App\Application(__DIR__ . '/../config');
    echo "Application created OK\n";
    
    $server = new Cake\Http\Server($app);
    echo "Server created OK\n";
    
    // Simulate a GET request to /login
    $_SERVER['REQUEST_METHOD'] = 'GET';
    $_SERVER['REQUEST_URI'] = '/login';
    $_SERVER['SERVER_NAME'] = 'localhost';
    $_SERVER['SERVER_PORT'] = '80';
    
    $response = $server->run();
    echo "Server run OK\n";
    echo "Status: " . $response->getStatusCode() . "\n";
    echo "Response body:\n";
    echo $response->getBody() . "\n";
    
} catch (Throwable $e) {
    echo "\n\n=== EXCEPTION CAUGHT ===\n";
    echo "Type: " . get_class($e) . "\n";
    echo "Message: " . $e->getMessage() . "\n";
    echo "File: " . $e->getFile() . "\n";
    echo "Line: " . $e->getLine() . "\n\n";
    echo "Stack trace:\n";
    echo $e->getTraceAsString() . "\n";
    
    if ($prev = $e->getPrevious()) {
        echo "\n\n=== PREVIOUS EXCEPTION ===\n";
        echo "Type: " . get_class($prev) . "\n";
        echo "Message: " . $prev->getMessage() . "\n";
        echo "File: " . $prev->getFile() . "\n";
        echo "Line: " . $prev->getLine() . "\n";
    }
}
