<?php
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../models/User.php';
require_once __DIR__ . '/../../utils/Mail.php';

header('Content-Type: application/json');

try {
    switch ($_SERVER['REQUEST_METHOD']) {
        case 'POST':
            $data = json_decode(file_get_contents('php://input'), true);
            
            if (!isset($data['token'])) {
                throw new Exception('Verification token is required');
            }

            $user = new User();
            $userData = $user->getByVerificationToken($data['token']);

            if (!$userData) {
                throw new Exception('Invalid or expired verification token');
            }

            // Update user verification status
            $success = $user->verifyEmail($userData['id']);
            
            if (!$success) {
                throw new Exception('Failed to verify email');
            }

            echo json_encode(['success' => true]);
            break;

        case 'PUT':
            // Resend verification email
            $data = json_decode(file_get_contents('php://input'), true);
            
            if (!isset($data['email'])) {
                throw new Exception('Email is required');
            }

            $user = new User();
            $userData = $user->getByEmail($data['email']);

            if (!$userData) {
                throw new Exception('User not found');
            }

            if ($userData['email_verified']) {
                throw new Exception('Email is already verified');
            }

            // Generate new verification token
            $token = bin2hex(random_bytes(32));
            $success = $user->updateVerificationToken($userData['id'], $token);
            
            if (!$success) {
                throw new Exception('Failed to generate verification token');
            }

            // Send verification email
            $mail = new Mail();
            $success = $mail->sendVerificationEmail(
                $userData['email'],
                $userData['name'],
                $token
            );
            
            if (!$success) {
                throw new Exception('Failed to send verification email');
            }

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