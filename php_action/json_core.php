<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/db_connect.php';

// Normalize legacy/new session key variants.
if (!isset($_SESSION['userId']) && isset($_SESSION['user_id'])) {
    $_SESSION['userId'] = $_SESSION['user_id'];
}
if (!isset($_SESSION['user_id']) && isset($_SESSION['userId'])) {
    $_SESSION['user_id'] = $_SESSION['userId'];
}

// Enforce auth for HTTP requests while still allowing local CLI diagnostics.
if (PHP_SAPI !== 'cli') {
    $hasUser = !empty($_SESSION['userId']) || !empty($_SESSION['user_id']);
    if (!$hasUser) {
        if (!headers_sent()) {
            header('Content-Type: application/json');
            http_response_code(401);
        }
        echo json_encode([
            'success' => false,
            'message' => 'Unauthorized access'
        ]);
        exit;
    }
}
