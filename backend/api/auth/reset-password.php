<?php
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../models/User.php';
require_once __DIR__ . '/../../utils/Mail.php';

header('Content-Type: application/json');

try {
    switch ($_SERVER['REQUEST_METHOD']) {
        case 'POST':
            // Request password reset
            $data = json_decode(file_get_contents('php://input'), true);
            
            if (!isset($data['email'])) {
                throw new Exception('Email is required');
            }

            $user = new User();
            $userData = $user->getByEmail($data['email']);

            if (!$userData) {
                // Don't reveal if email exists
                echo json_encode(['success' => true]);
                exit;
            }

            // Generate reset token
            $token = bin2hex(random_bytes(32));
            $expires = date('Y-m-d H:i:s', strtotime('+1 hour'));
            
            $success = $user->updateResetToken($userData['id'], $token, $expires);
            
            if (!$success) {
                throw new Exception('Failed to generate reset token');
            }

            // Send reset email
            $mail = new Mail();
            $success = $mail->sendPasswordResetEmail(
                $userData['email'],
                $userData['name'],
                $token
            );
            
            if (!$success) {
                throw new Exception('Failed to send reset email');
            }

            echo json_encode(['success' => true]);
            break;

        case 'PUT':
            // Reset password
            $data = json_decode(file_get_contents('php://input'), true);
            
            if (!isset($data['token']) || !isset($data['password'])) {
                throw new Exception('Token and new password are required');
            }

            $user = new User();
            $userData = $user->getByResetToken($data['token']);

            if (!$userData) {
                throw new Exception('Invalid or expired reset token');
            }

            // Verify token hasn't expired
            if (strtotime($userData['reset_token_expires']) < time()) {
                throw new Exception('Reset token has expired');
            }

            // Validate password
            if (strlen($data['password']) < 8) {
                throw new Exception('Password must be at least 8 characters long');
            }

            // Update password and clear reset token
            $success = $user->resetPassword($userData['id'], $data['password']);
            
            if (!$success) {
                throw new Exception('Failed to reset password');
            }

            // Send confirmation email
            $mail = new Mail();
            $mail->sendPasswordChangeNotification(
                $userData['email'],
                $userData['name']
            );

            echo json_encode(['success' => true]);
            break;

        default:
            http_response_code(405);
            echo json_encode(['error' => 'Method not allowed']);
            exit;
    }

} catch (Exception $e) {
    http_response_code(400);
    echo json_encode(['error' => $e->getMessage()]);
} 