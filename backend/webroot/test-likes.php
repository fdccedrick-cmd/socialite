<?php
// Direct test - bypass CakePHP
error_log("=== TEST-LIKES.PHP CALLED ===");
header('Content-Type: application/json');
echo json_encode([
    'success' => true,
    'message' => 'Direct PHP file works',
    'method' => $_SERVER['REQUEST_METHOD'] ?? 'unknown'
]);
