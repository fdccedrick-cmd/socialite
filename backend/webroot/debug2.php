<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);

try {
    require __DIR__ . '/../vendor/autoload.php';
    
    $app = new App\Application(__DIR__ . '/../config');
    $server = new Cake\Http\Server($app);
    
    echo "Server created OK<br>";
    
    // Try to run a request
    $response = $server->run();
    echo "Request processed<br>";
    
    $server->emit($response);
    
} catch (Exception $e) {
    echo "ERROR: " . $e->getMessage() . "<br>";
    echo "File: " . $e->getFile() . ":" . $e->getLine() . "<br>";
    echo "<pre>" . htmlspecialchars($e->getTraceAsString()) . "</pre>";
} catch (Error $e) {
    echo "FATAL ERROR: " . $e->getMessage() . "<br>";
    echo "File: " . $e->getFile() . ":" . $e->getLine() . "<br>";
    echo "<pre>" . htmlspecialchars($e->getTraceAsString()) . "</pre>";
}
