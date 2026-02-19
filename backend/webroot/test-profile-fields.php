<?php
// Test script to verify the new profile fields can be saved to database
require dirname(__DIR__) . '/vendor/autoload.php';

use Cake\Core\Configure;
use Cake\Datasource\ConnectionManager;
use Cake\ORM\TableRegistry;

// Bootstrap CakePHP
require dirname(__DIR__) . '/config/bootstrap.php';

echo "Testing profile fields save...\n\n";

try {
    // Get the Users table
    $usersTable = TableRegistry::getTableLocator()->get('Users');
    
    // Find a test user (assuming user ID 1 exists)
    $user = $usersTable->find()->first();
    
    if (!$user) {
        echo "ERROR: No users found in database\n";
        exit(1);
    }
    
    echo "Testing with user ID: {$user->id} ({$user->username})\n\n";
    
    // Test data
    $testData = [
        'address' => '123 Test Street, Test City',
        'relationship_status' => 'single',
        'contact_links' => json_encode([
            ['label' => 'Instagram', 'url' => 'https://instagram.com/test'],
            ['label' => 'Twitter', 'url' => 'https://twitter.com/test']
        ])
    ];
    
    echo "Attempting to save:\n";
    echo "- Address: {$testData['address']}\n";
    echo "- Relationship Status: {$testData['relationship_status']}\n";
    echo "- Contact Links: {$testData['contact_links']}\n\n";
    
    // Patch and save
    $user = $usersTable->patchEntity($user, $testData);
    
    if ($user->hasErrors()) {
        echo "VALIDATION ERRORS:\n";
        print_r($user->getErrors());
        exit(1);
    }
    
    $result = $usersTable->save($user);
    
    if ($result) {
        echo "✓ Save successful!\n\n";
        
        // Fetch fresh from database
        $fresh = $usersTable->get($user->id);
        
        echo "Values from database:\n";
        echo "- Address: " . ($fresh->address ?? 'NULL') . "\n";
        echo "- Relationship Status: " . ($fresh->relationship_status ?? 'NULL') . "\n";
        echo "- Contact Links: " . ($fresh->contact_links ?? 'NULL') . "\n\n";
        
        // Verify data matches
        $success = true;
        if ($fresh->address !== $testData['address']) {
            echo "✗ Address mismatch!\n";
            $success = false;
        } else {
            echo "✓ Address matches\n";
        }
        
        if ($fresh->relationship_status !== $testData['relationship_status']) {
            echo "✗ Relationship status mismatch!\n";
            $success = false;
        } else {
            echo "✓ Relationship status matches\n";
        }
        
        if ($fresh->contact_links !== $testData['contact_links']) {
            echo "✗ Contact links mismatch!\n";
            $success = false;
        } else {
            echo "✓ Contact links matches\n";
        }
        
        if ($success) {
            echo "\n✓✓✓ ALL TESTS PASSED! ✓✓✓\n";
        } else {
            echo "\n✗✗✗ SOME TESTS FAILED ✗✗✗\n";
        }
    } else {
        echo "✗ Save failed!\n";
        if ($user->hasErrors()) {
            echo "Errors:\n";
            print_r($user->getErrors());
        }
    }
    
} catch (Exception $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
    echo $e->getTraceAsString() . "\n";
    exit(1);
}
