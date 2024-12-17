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
            // Get available themes
            $db = Database::getInstance();
            $themes = $db->getRows("SELECT * FROM themes ORDER BY is_default DESC, name ASC");

            // If device_id is provided, get the current theme
            if (isset($_GET['device_id'])) {
                if (!$device->validateOwnership($_GET['device_id'], $userData['user_id'])) {
                    throw new Exception('Device not found or access denied');
                }

                $settings = $device->getSettings($_GET['device_id']);
                $currentTheme = $settings['theme'] ?? 'default';

                echo json_encode([
                    'themes' => $themes,
                    'current_theme' => $currentTheme
                ]);
            } else {
                echo json_encode(['themes' => $themes]);
            }
            break;

        case 'PUT':
        case 'POST':
            $data = json_decode(file_get_contents('php://input'), true);
            
            if (!isset($data['device_id']) || !isset($data['theme'])) {
                throw new Exception('Device ID and theme are required');
            }

            // Verify device ownership
            if (!$device->validateOwnership($data['device_id'], $userData['user_id'])) {
                throw new Exception('Device not found or access denied');
            }

            // Verify theme exists
            $db = Database::getInstance();
            $theme = $db->getRow(
                "SELECT * FROM themes WHERE name = :name",
                ['name' => $data['theme']]
            );

            if (!$theme) {
                throw new Exception('Invalid theme');
            }

            // Update device theme
            $success = $device->updateSettings($data['device_id'], [
                'theme' => $data['theme']
            ]);
            
            if (!$success) {
                throw new Exception('Failed to update theme');
            }

            echo json_encode([
                'success' => true,
                'theme' => $theme
            ]);
            break;

        case 'DELETE':
            // Only allow deleting custom themes
            if (!isset($_GET['name'])) {
                throw new Exception('Theme name is required');
            }

            $db = Database::getInstance();
            $theme = $db->getRow(
                "SELECT * FROM themes WHERE name = :name",
                ['name' => $_GET['name']]
            );

            if (!$theme) {
                throw new Exception('Theme not found');
            }

            if ($theme['is_default']) {
                throw new Exception('Cannot delete default theme');
            }

            // Delete theme
            $success = $db->query(
                "DELETE FROM themes WHERE name = :name AND is_default = FALSE",
                ['name' => $_GET['name']]
            );

            if (!$success) {
                throw new Exception('Failed to delete theme');
            }

            // Reset devices using this theme to default
            $db->query(
                "UPDATE device_settings SET setting_value = 'default' 
                 WHERE setting_key = 'theme' AND setting_value = :theme",
                ['theme' => $_GET['name']]
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