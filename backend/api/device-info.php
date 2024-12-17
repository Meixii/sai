<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../utils/Auth.php';
require_once __DIR__ . '/../models/Device.php';

header('Content-Type: application/json');

try {
    // Verify authentication
    $userData = Auth::requireAuth();
    
    $device = new Device();
    $devices = $device->getByUserId($userData['user_id']);
    
    // Get settings for each device
    $devicesWithSettings = array_map(function($deviceData) use ($device) {
        $deviceData['settings'] = $device->getSettings($deviceData['device_id']);
        return $deviceData;
    }, $devices);

    echo json_encode([
        'devices' => $devicesWithSettings,
        'total_devices' => count($devices),
        'active_devices' => count(array_filter($devices, function($d) {
            return strtotime($d['last_sync']) > strtotime('-5 minutes');
        }))
    ]);

} catch (Exception $e) {
    http_response_code(400);
    echo json_encode(['error' => $e->getMessage()]);
} 