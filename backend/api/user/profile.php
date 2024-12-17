<?php
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../utils/Auth.php';
require_once __DIR__ . '/../../models/User.php';

header('Content-Type: application/json');

try {
    // Verify authentication
    $userData = Auth::requireAuth();
    
    $user = new User();
    
    switch ($_SERVER['REQUEST_METHOD']) {
        case 'GET':
            // Get user profile
            $profile = $user->getById($userData['user_id']);
            
            if (!$profile) {
                throw new Exception('User not found');
            }

            // Remove sensitive data
            unset($profile['password_hash']);
            unset($profile['verification_token']);
            unset($profile['reset_token']);
            unset($profile['reset_token_expires']);

            echo json_encode(['profile' => $profile]);
            break;

        case 'PUT':
        case 'POST':
            $data = json_decode(file_get_contents('php://input'), true);
            
            // Validate input
            if (!isset($data['name'])) {
                throw new Exception('Name is required');
            }

            // Update profile
            $success = $user->updateProfile($userData['user_id'], [
                'name' => $data['name']
            ]);
            
            if (!$success) {
                throw new Exception('Failed to update profile');
            }

            // Get updated profile
            $profile = $user->getById($userData['user_id']);
            unset($profile['password_hash']);
            unset($profile['verification_token']);
            unset($profile['reset_token']);
            unset($profile['reset_token_expires']);

            echo json_encode(['profile' => $profile]);
            break;

        case 'PATCH':
            // For password update
            $data = json_decode(file_get_contents('php://input'), true);
            
            if (!isset($data['current_password']) || !isset($data['new_password'])) {
                throw new Exception('Current password and new password are required');
            }

            // Verify current password
            $currentUser = $user->getById($userData['user_id']);
            if (!password_verify($data['current_password'], $currentUser['password_hash'])) {
                throw new Exception('Current password is incorrect');
            }

            // Validate new password
            if (strlen($data['new_password']) < 8) {
                throw new Exception('New password must be at least 8 characters long');
            }

            // Update password
            $success = $user->updatePassword($userData['user_id'], $data['new_password']);
            
            if (!$success) {
                throw new Exception('Failed to update password');
            }

            echo json_encode(['success' => true, 'message' => 'Password updated successfully']);
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