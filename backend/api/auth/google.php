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
    
    if (!isset($data['credential'])) {
        throw new Exception('Google credential is required');
    }

    // Verify Google token
    $client = new Google_Client(['client_id' => GOOGLE_CLIENT_ID]);
    $payload = $client->verifyIdToken($data['credential']);

    if (!$payload) {
        throw new Exception('Invalid Google token');
    }

    // Create or update user
    $user = new User();
    $userData = $user->createOrUpdateFromGoogle([
        'sub' => $payload['sub'],
        'email' => $payload['email'],
        'name' => $payload['name'],
        'picture' => $payload['picture']
    ]);

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