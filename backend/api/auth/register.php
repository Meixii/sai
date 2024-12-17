<?php
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../utils/Auth.php';
require_once __DIR__ . '/../../models/User.php';
require_once __DIR__ . '/../../utils/Mail.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit;
}

try {
    $data = json_decode(file_get_contents('php://input'), true);
    
    if (!isset($data['email']) || !isset($data['password']) || !isset($data['name'])) {
        throw new Exception('Email, password, and name are required');
    }

    // Validate email
    if (!filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
        throw new Exception('Invalid email format');
    }

    // Validate password
    if (strlen($data['password']) < 8) {
        throw new Exception('Password must be at least 8 characters long');
    }

    // Check if email already exists
    $user = new User();
    $existingUser = $user->getByEmail($data['email']);
    
    if ($existingUser) {
        throw new Exception('Email already registered');
    }

    // Create user
    $userId = $user->create([
        'email' => $data['email'],
        'password' => $data['password'],
        'name' => $data['name']
    ]);

    if (!$userId) {
        throw new Exception('Failed to create user');
    }

    // Generate verification token
    $token = bin2hex(random_bytes(32));
    $success = $user->updateVerificationToken($userId, $token);
    
    if (!$success) {
        throw new Exception('Failed to generate verification token');
    }

    // Log SMTP settings
    error_log("SMTP Settings - Host: " . SMTP_HOST . ", Port: " . SMTP_PORT . ", User: " . SMTP_USER);

    // Send verification email
    $mail = new Mail();
    try {
        $success = $mail->sendVerificationEmail(
            $data['email'],
            $data['name'],
            $token
        );
        
        if (!$success) {
            error_log("Failed to send verification email to " . $data['email']);
            throw new Exception('Failed to send verification email');
        }
        
        error_log("Successfully sent verification email to " . $data['email']);
    } catch (Exception $e) {
        error_log("Email error: " . $e->getMessage());
        throw new Exception('Failed to send verification email: ' . $e->getMessage());
    }

    echo json_encode([
        'success' => true,
        'message' => 'Registration successful. Please check your email to verify your account.'
    ]);

} catch (Exception $e) {
    error_log("Registration error: " . $e->getMessage());
    http_response_code(400);
    echo json_encode(['error' => $e->getMessage()]);
} 