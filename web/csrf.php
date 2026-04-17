<?php
/**
 * CSRF Token helper functions.
 * Include after session_start() in every file that handles forms.
 */

function generateCSRFToken() {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function validateCSRFToken($token) {
    if (!isset($_SESSION['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $token ?? '')) {
        http_response_code(403);
        die('CSRF validation failed');
    }
}

function h($value) {
    return htmlspecialchars($value ?? '', ENT_QUOTES, 'UTF-8');
}
?>
