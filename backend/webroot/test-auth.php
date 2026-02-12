<?php
// Test authentication identifier
error_reporting(E_ALL);
ini_set('display_errors', '1');

require __DIR__ . '/../vendor/autoload.php';

use Cake\Datasource\ConnectionManager;
use Cake\ORM\TableRegistry;
use Cake\Core\Configure;
use Authentication\Identifier\PasswordIdentifier;

try {
    echo "1. Setting up application...\n";
    $app = new App\Application(__DIR__ . '/../config');
    $app->bootstrap();
    
    echo "2. Loading Users table...\n";
    $usersTable = TableRegistry::getTableLocator()->get('Users');
    
    echo "3. Finding user 'johndoe'...\n";
    $user = $usersTable->find()->where(['username' => 'johndoe'])->first();
    
    if ($user) {
        echo "   User found!\n";
        echo "   ID: " . $user->id . "\n";
        echo "   Username: " . $user->username . "\n";
        echo "   Full name: " . $user->full_name . "\n";
        echo "   Password hash: " . substr($user->password_hash, 0, 20) . "...\n";
    } else {
        echo "   User NOT found!\n";
    }
    
    echo "\n4. Testing password identifier...\n";
    $identifier = new PasswordIdentifier();
    $identifier->setConfig([
        'fields' => [
            'username' => 'username',
            'password' => 'password_hash',
        ],
        'resolver' => [
            'className' => 'Authentication.Orm',
            'userModel' => 'Users',
        ],
    ]);
    
    $credentials = [
        'username' => 'johndoe',
        'password' => 'password123',
    ];
    
    echo "5. Attempting to identify user...\n";
    $result = $identifier->identify($credentials);
    
    if ($result->isValid()) {
        echo "   SUCCESS! User authenticated\n";
        $data = $result->getData();
        echo "   User ID: " . $data->id . "\n";
        echo "   Username: " . $data->username . "\n";
    } else {
        echo "   FAILED! Authentication failed\n";
        $errors = $result->getErrors();
        print_r($errors);
    }
    
} catch (Throwable $e) {
    echo "\n\n=== EXCEPTION ===\n";
    echo "Type: " . get_class($e) . "\n";
    echo "Message: " . $e->getMessage() . "\n";
    echo "File: " . $e->getFile() . ":" . $e->getLine() . "\n\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
}
