<?php
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../utils/Auth.php';
require_once __DIR__ . '/../../models/Device.php';
require_once __DIR__ . '/../../models/Alarm.php';
require_once __DIR__ . '/../../models/Calendar.php';
require_once __DIR__ . '/../../models/Weather.php';

header('Content-Type: application/json');

try {
    // Verify authentication
    $userData = Auth::requireAuth();
    
    $device = new Device();
    $alarm = new Alarm();
    $calendar = new Calendar();
    $weather = new Weather();
    
    // Get all user's devices
    $devices = $device->getByUserId($userData['user_id']);
    
    // Get active devices count
    $activeDevices = count(array_filter($devices, function($d) {
        return strtotime($d['last_sync']) > strtotime('-5 minutes');
    }));
    
    // Get alarms statistics
    $totalAlarms = 0;
    $activeAlarms = 0;
    foreach ($devices as $deviceData) {
        $alarms = $alarm->getByDeviceId($deviceData['device_id']);
        $totalAlarms += count($alarms);
        $activeAlarms += count(array_filter($alarms, function($a) {
            return $a['enabled'];
        }));
    }
    
    // Get today's events count
    $todayEvents = 0;
    $hasCalendar = $calendar->hasValidIntegration($userData['user_id']);
    if ($hasCalendar) {
        $events = $calendar->getUpcomingEvents($userData['user_id'], 1);
        if ($events) {
            $today = date('Y-m-d');
            $todayEvents = count(array_filter($events, function($e) use ($today) {
                return date('Y-m-d', strtotime($e['start'])) === $today;
            }));
        }
    }
    
    // Get current weather
    $weatherInfo = [
        'temperature' => null,
        'condition' => 'Unknown',
        'icon' => '',
        'humidity' => null,
        'wind_speed' => null
    ];
    
    try {
        $weatherData = $weather->getCachedWeather('auto:ip');
        if (!$weatherData) {
            $weatherData = $weather->getCurrentWeather('auto:ip');
        }
        if ($weatherData && isset($weatherData['current'])) {
            $weatherInfo = [
                'temperature' => $weatherData['current']['temp_c'] ?? null,
                'condition' => $weatherData['current']['condition']['text'] ?? 'Unknown',
                'icon' => $weatherData['current']['condition']['icon'] ?? '',
                'humidity' => $weatherData['current']['humidity'] ?? null,
                'wind_speed' => $weatherData['current']['wind_kph'] ?? null
            ];
        }
    } catch (Exception $weatherError) {
        error_log("Weather API Error: " . $weatherError->getMessage());
    }
    
    // Get system status
    $db = Database::getInstance();
    $lastSync = $db->getRow(
        "SELECT MAX(last_sync) as last_sync FROM devices WHERE user_id = :user_id",
        ['user_id' => $userData['user_id']]
    );
    
    $nextAlarm = null;
    if (!empty($devices)) {
        try {
            $nextAlarm = $alarm->getNextAlarm($devices[0]['device_id']);
        } catch (Exception $alarmError) {
            error_log("Next Alarm Error: " . $alarmError->getMessage());
        }
    }
    
    echo json_encode([
        'devices' => [
            'total' => count($devices),
            'active' => $activeDevices,
            'last_sync' => $lastSync['last_sync'] ?? null
        ],
        'alarms' => [
            'total' => $totalAlarms,
            'active' => $activeAlarms,
            'next' => $nextAlarm
        ],
        'events' => [
            'today' => $todayEvents,
            'has_calendar' => $hasCalendar
        ],
        'weather' => $weatherInfo,
        'system_status' => [
            'last_update' => date('Y-m-d H:i:s'),
            'status' => 'operational'
        ]
    ]);

} catch (Exception $e) {
    error_log("Overview API Error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
} 