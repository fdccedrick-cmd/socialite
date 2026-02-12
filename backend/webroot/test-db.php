<?php
// Enable all error reporting
error_reporting(E_ALL);
ini_set('display_errors', '1');

require __DIR__ . '/../vendor/autoload.php';

use Cake\Datasource\ConnectionManager;
use Cake\ORM\TableRegistry;
use Cake\Core\Configure;

try {
    echo "1. Autoload OK\n";
    
    // Check if authentication classes exist
    if (class_exists('Authentication\AuthenticationService')) {
        echo "2. Authentication plugin loaded OK\n";
    } else {
        echo "2. ERROR: Authentication plugin NOT loaded\n";
    }
    
    // Try to create the application
    $app = new App\Application(__DIR__ . '/../config');
    $app->bootstrap();
    echo "3. Application bootstrapped OK\n";
    
    // Check if datasources are configured
    $datasources = Configure::read('Datasources');
    if ($datasources) {
        echo "4. Datasources configuration found\n";
        print_r(array_keys($datasources));
    } else {
        echo "4. ERROR: No datasources configuration\n";
    }
    
    // Test database connection
    $connection = ConnectionManager::get('default');
    $connection->getDriver()->connect();
    echo "5. Database connection OK\n";
    
    // Try to query users table
    $result = $connection->execute('SELECT COUNT(*) as count FROM users')->fetch('assoc');
    echo "6. Users table exists, count: " . $result['count'] . "\n";
    
    // Check if we can load the Users table
    $usersTable = TableRegistry::getTableLocator()->get('Users');
    echo "7. UsersTable loaded OK\n";
    
    // Try to find all users
    $users = $usersTable->find()->all();
    echo "8. Found " . $users->count() . " users\n";
    
    foreach ($users as $user) {
        echo "   - " . $user->username . " (" . $user->full_name . ")\n";
    }
    
} catch (Throwable $e) {
    echo "\n\n=== EXCEPTION ===\n";
    echo "Type: " . get_class($e) . "\n";
    echo "Message: " . $e->getMessage() . "\n";
    echo "File: " . $e->getFile() . ":" . $e->getLine() . "\n\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
}

