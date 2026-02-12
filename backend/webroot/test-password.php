<?php
// Test password verification
$password = 'password123';
$hash = '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi';

echo "Testing password: $password\n";
echo "Against hash: $hash\n\n";

if (password_verify($password, $hash)) {
    echo "✓ Password matches!\n";
} else {
    echo "✗ Password does NOT match!\n";
    
    // Generate correct hash
    $correct_hash = password_hash($password, PASSWORD_DEFAULT);
    echo "\nCorrect hash for '$password' would be:\n";
    echo $correct_hash . "\n";
}
