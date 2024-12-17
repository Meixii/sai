<?php
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../utils/Auth.php';
require_once __DIR__ . '/../../models/Device.php';
require_once __DIR__ . '/../../models/Alarm.php';

header('Content-Type: application/json');

try {
    // Verify authentication
    $userData = Auth::requireAuth();
    
    $db = Database::getInstance();
    
    // Get recent activities (last 10)
    $query = "SELECT a.*, d.name as device_name 
              FROM (
                  SELECT 'alarm' as type, id, device_id, 'Alarm updated' as title, updated_at as timestamp 
                  FROM alarms 
                  WHERE updated_at IS NOT NULL
                  UNION ALL
                  SELECT 'device' as type, id, device_id, 'Device synced' as title, last_sync as timestamp 
                  FROM devices 
                  WHERE last_sync IS NOT NULL
                  UNION ALL
                  SELECT 'settings' as type, id, device_id, 'Settings changed' as title, updated_at as timestamp 
                  FROM device_settings 
                  WHERE updated_at IS NOT NULL
              ) a
              LEFT JOIN devices d ON a.device_id = d.device_id
              WHERE d.user_id = :user_id
              ORDER BY timestamp DESC
              LIMIT 10";
    
    $activities = $db->getRows($query, ['user_id' => $userData['user_id']]);
    
    echo json_encode([
        'activities' => array_map(function($activity) {
            return [
                'type' => $activity['type'],
                'title' => $activity['title'] . ' - ' . $activity['device_name'],
                'timestamp' => $activity['timestamp']
            ];
        }, $activities)
    ]);

} catch (Exception $e) {
    http_response_code(400);
    echo json_encode(['error' => $e->getMessage()]);
} 