<?php
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../utils/Auth.php';
require_once __DIR__ . '/../../models/Device.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit;
}

try {
    // Verify authentication
    $userData = Auth::requireAuth();
    
    $data = json_decode(file_get_contents('php://input'), true);
    
    if (!isset($data['name'])) {
        throw new Exception('Device name is required');
    }

    $device = new Device();
    $deviceId = $device->register([
        'user_id' => $userData['user_id'],
        'name' => $data['name']
    ]);

    if (!$deviceId) {
        throw new Exception('Failed to register device');
    }

    // Get the full device data
    $deviceData = $device->getById($deviceId);
    $settings = $device->getSettings($deviceId);

    echo json_encode([
        'device' => $deviceData,
        'settings' => $settings
    ]);

} catch (Exception $e) {
    http_response_code(400);
    echo json_encode(['error' => $e->getMessage()]);
} 