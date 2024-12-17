<?php
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../utils/Auth.php';
require_once __DIR__ . '/../../models/Device.php';

header('Content-Type: application/json');

try {
    // Verify authentication
    $userData = Auth::requireAuth();
    
    $device = new Device();
    
    switch ($_SERVER['REQUEST_METHOD']) {
        case 'GET':
            if (!isset($_GET['device_id'])) {
                throw new Exception('Device ID is required');
            }

            // Verify device ownership
            if (!$device->validateOwnership($_GET['device_id'], $userData['user_id'])) {
                throw new Exception('Device not found or access denied');
            }

            $settings = $device->getSettings($_GET['device_id']);
            echo json_encode(['settings' => $settings]);
            break;

        case 'PUT':
        case 'POST':
            $data = json_decode(file_get_contents('php://input'), true);
            
            if (!isset($data['device_id']) || !isset($data['settings'])) {
                throw new Exception('Device ID and settings are required');
            }

            // Verify device ownership
            if (!$device->validateOwnership($data['device_id'], $userData['user_id'])) {
                throw new Exception('Device not found or access denied');
            }

            // Validate settings
            $allowedSettings = [
                'theme', 'display_format', 'date_format', 'temperature_unit',
                'rgb_enabled', 'rgb_color', 'alarm_sound'
            ];

            foreach ($data['settings'] as $key => $value) {
                if (!in_array($key, $allowedSettings)) {
                    throw new Exception("Invalid setting: {$key}");
                }
            }

            $success = $device->updateSettings($data['device_id'], $data['settings']);
            
            if (!$success) {
                throw new Exception('Failed to update settings');
            }

            $updatedSettings = $device->getSettings($data['device_id']);
            echo json_encode(['settings' => $updatedSettings]);
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