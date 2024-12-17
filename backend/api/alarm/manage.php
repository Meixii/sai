<?php
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../utils/Auth.php';
require_once __DIR__ . '/../../models/Device.php';
require_once __DIR__ . '/../../models/Alarm.php';

header('Content-Type: application/json');

try {
    // Verify authentication
    $userData = Auth::requireAuth();
    
    $device = new Device();
    $alarm = new Alarm();
    
    switch ($_SERVER['REQUEST_METHOD']) {
        case 'GET':
            if (!isset($_GET['device_id'])) {
                throw new Exception('Device ID is required');
            }

            // Verify device ownership
            if (!$device->validateOwnership($_GET['device_id'], $userData['user_id'])) {
                throw new Exception('Device not found or access denied');
            }

            // Get specific alarm if ID is provided
            if (isset($_GET['alarm_id'])) {
                $alarmData = $alarm->getById($_GET['alarm_id']);
                if (!$alarmData || $alarmData['device_id'] !== $_GET['device_id']) {
                    throw new Exception('Alarm not found');
                }
                echo json_encode(['alarm' => $alarmData]);
            } else {
                // Get all alarms for device
                $alarms = $alarm->getByDeviceId($_GET['device_id']);
                echo json_encode(['alarms' => $alarms]);
            }
            break;

        case 'POST':
            $data = json_decode(file_get_contents('php://input'), true);
            
            if (!isset($data['device_id']) || !isset($data['time'])) {
                throw new Exception('Device ID and time are required');
            }

            // Verify device ownership
            if (!$device->validateOwnership($data['device_id'], $userData['user_id'])) {
                throw new Exception('Device not found or access denied');
            }

            // Create new alarm
            $alarmId = $alarm->create($data);
            
            if (!$alarmId) {
                throw new Exception('Failed to create alarm');
            }

            $alarmData = $alarm->getById($alarmId);
            echo json_encode(['alarm' => $alarmData]);
            break;

        case 'PUT':
            $data = json_decode(file_get_contents('php://input'), true);
            
            if (!isset($data['alarm_id'])) {
                throw new Exception('Alarm ID is required');
            }

            // Get alarm to verify ownership
            $existingAlarm = $alarm->getById($data['alarm_id']);
            if (!$existingAlarm) {
                throw new Exception('Alarm not found');
            }

            // Verify device ownership
            if (!$device->validateOwnership($existingAlarm['device_id'], $userData['user_id'])) {
                throw new Exception('Access denied');
            }

            // Update alarm
            $success = $alarm->update($data['alarm_id'], $data);
            
            if (!$success) {
                throw new Exception('Failed to update alarm');
            }

            $updatedAlarm = $alarm->getById($data['alarm_id']);
            echo json_encode(['alarm' => $updatedAlarm]);
            break;

        case 'DELETE':
            if (!isset($_GET['alarm_id'])) {
                throw new Exception('Alarm ID is required');
            }

            // Get alarm to verify ownership
            $existingAlarm = $alarm->getById($_GET['alarm_id']);
            if (!$existingAlarm) {
                throw new Exception('Alarm not found');
            }

            // Verify device ownership
            if (!$device->validateOwnership($existingAlarm['device_id'], $userData['user_id'])) {
                throw new Exception('Access denied');
            }

            // Delete alarm
            $success = $alarm->delete($_GET['alarm_id']);
            
            if (!$success) {
                throw new Exception('Failed to delete alarm');
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