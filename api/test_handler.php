<?php
// Simple test to see if API folder is accessible
header('Content-Type: application/json');
echo json_encode([
    'success' => true,
    'message' => 'API handler is accessible!',
    'timestamp' => date('Y-m-d H:i:s')
]);
?>
