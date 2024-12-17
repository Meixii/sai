<?php
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../utils/Auth.php';
require_once __DIR__ . '/../../models/Device.php';

header('Content-Type: application/json');

try {
    // Verify authentication
    $userData = Auth::requireAuth();
    
    $device = new Device();
    $db = Database::getInstance();
    
    switch ($_SERVER['REQUEST_METHOD']) {
        case 'GET':
            // Get available alarm sounds
            $query = "SELECT * FROM alarm_sounds WHERE is_default = TRUE OR user_id = :user_id ORDER BY is_default DESC, name ASC";
            $sounds = $db->getRows($query, ['user_id' => $userData['user_id']]);

            // If device_id is provided, get the current sound
            if (isset($_GET['device_id'])) {
                if (!$device->validateOwnership($_GET['device_id'], $userData['user_id'])) {
                    throw new Exception('Device not found or access denied');
                }

                $settings = $device->getSettings($_GET['device_id']);
                $currentSound = $settings['alarm_sound'] ?? 'default.mp3';

                echo json_encode([
                    'sounds' => $sounds,
                    'current_sound' => $currentSound
                ]);
            } else {
                echo json_encode(['sounds' => $sounds]);
            }
            break;

        case 'POST':
            // Upload new alarm sound
            if (!isset($_FILES['sound'])) {
                throw new Exception('No sound file uploaded');
            }

            $file = $_FILES['sound'];
            $name = $_POST['name'] ?? pathinfo($file['name'], PATHINFO_FILENAME);

            // Validate file
            $allowedTypes = ['audio/mpeg', 'audio/mp3', 'audio/wav'];
            if (!in_array($file['type'], $allowedTypes)) {
                throw new Exception('Invalid file type. Only MP3 and WAV files are allowed.');
            }

            if ($file['size'] > 5 * 1024 * 1024) { // 5MB limit
                throw new Exception('File too large. Maximum size is 5MB.');
            }

            // Generate unique filename
            $filename = uniqid() . '_' . preg_replace('/[^a-zA-Z0-9]/', '_', $name) . '.mp3';
            $uploadPath = __DIR__ . '/../../uploads/sounds/' . $filename;

            // Create directory if it doesn't exist
            if (!is_dir(dirname($uploadPath))) {
                mkdir(dirname($uploadPath), 0777, true);
            }

            // Move uploaded file
            if (!move_uploaded_file($file['tmp_name'], $uploadPath)) {
                throw new Exception('Failed to upload file');
            }

            // Add to database
            $query = "INSERT INTO alarm_sounds (name, filename, user_id, created_at) VALUES (:name, :filename, :user_id, NOW())";
            $success = $db->query($query, [
                'name' => $name,
                'filename' => $filename,
                'user_id' => $userData['user_id']
            ]);

            if (!$success) {
                unlink($uploadPath);
                throw new Exception('Failed to save sound');
            }

            $soundId = $db->lastInsertId();
            $sound = $db->getRow("SELECT * FROM alarm_sounds WHERE id = :id", ['id' => $soundId]);

            echo json_encode(['sound' => $sound]);
            break;

        case 'PUT':
            $data = json_decode(file_get_contents('php://input'), true);
            
            if (!isset($data['device_id']) || !isset($data['sound'])) {
                throw new Exception('Device ID and sound are required');
            }

            // Verify device ownership
            if (!$device->validateOwnership($data['device_id'], $userData['user_id'])) {
                throw new Exception('Device not found or access denied');
            }

            // Verify sound exists
            $sound = $db->getRow(
                "SELECT * FROM alarm_sounds WHERE filename = :filename AND (is_default = TRUE OR user_id = :user_id)",
                [
                    'filename' => $data['sound'],
                    'user_id' => $userData['user_id']
                ]
            );

            if (!$sound) {
                throw new Exception('Invalid sound');
            }

            // Update device sound
            $success = $device->updateSettings($data['device_id'], [
                'alarm_sound' => $data['sound']
            ]);
            
            if (!$success) {
                throw new Exception('Failed to update sound');
            }

            echo json_encode([
                'success' => true,
                'sound' => $sound
            ]);
            break;

        case 'DELETE':
            if (!isset($_GET['id'])) {
                throw new Exception('Sound ID is required');
            }

            // Get sound details
            $sound = $db->getRow(
                "SELECT * FROM alarm_sounds WHERE id = :id AND user_id = :user_id",
                [
                    'id' => $_GET['id'],
                    'user_id' => $userData['user_id']
                ]
            );

            if (!$sound) {
                throw new Exception('Sound not found or access denied');
            }

            if ($sound['is_default']) {
                throw new Exception('Cannot delete default sound');
            }

            // Delete file
            $filePath = __DIR__ . '/../../uploads/sounds/' . $sound['filename'];
            if (file_exists($filePath)) {
                unlink($filePath);
            }

            // Delete from database
            $success = $db->query(
                "DELETE FROM alarm_sounds WHERE id = :id AND user_id = :user_id",
                [
                    'id' => $_GET['id'],
                    'user_id' => $userData['user_id']
                ]
            );

            if (!$success) {
                throw new Exception('Failed to delete sound');
            }

            // Reset devices using this sound to default
            $db->query(
                "UPDATE device_settings SET setting_value = 'default.mp3' 
                 WHERE setting_key = 'alarm_sound' AND setting_value = :filename",
                ['filename' => $sound['filename']]
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