<?php
error_log("=== TEST ENDPOINT CALLED ===");
header('Content-Type: application/json');
echo json_encode([
    'success' => true,
    'message' => 'Test endpoint works',
    'method' => $_SERVER['REQUEST_METHOD'] ?? 'unknown',
    'timestamp' => time()
]);
