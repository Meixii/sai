<?php
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../utils/Auth.php';
require_once __DIR__ . '/../../models/User.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit;
}

try {
    $data = json_decode(file_get_contents('php://input'), true);
    
    if (!isset($data['email']) || !isset($data['password'])) {
        throw new Exception('Email and password are required');
    }

    $user = new User();
    $userData = $user->verifyPassword($data['email'], $data['password']);

    if (!$userData) {
        throw new Exception('Invalid credentials');
    }

    if (!$userData['email_verified']) {
        throw new Exception('Email not verified');
    }

    // Generate JWT token
    $token = Auth::generateToken([
        'user_id' => $userData['id'],
        'email' => $userData['email']
    ]);

    // Update last login
    $user->updateLastLogin($userData['id']);

    echo json_encode([
        'token' => $token,
        'user' => [
            'id' => $userData['id'],
            'email' => $userData['email'],
            'name' => $userData['name'],
            'picture' => $userData['picture']
        ]
    ]);

} catch (Exception $e) {
    http_response_code(401);
    echo json_encode(['error' => $e->getMessage()]);
} 