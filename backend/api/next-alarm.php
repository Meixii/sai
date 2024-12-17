<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../utils/Auth.php';
require_once __DIR__ . '/../models/Device.php';
require_once __DIR__ . '/../models/Alarm.php';

header('Content-Type: application/json');

try {
    // Verify authentication
    $userData = Auth::requireAuth();
    
    $device = new Device();
    $alarm = new Alarm();
    
    // Get all user's devices
    $devices = $device->getByUserId($userData['user_id']);
    
    $nextAlarm = null;
    $now = new DateTime();
    
    foreach ($devices as $deviceData) {
        // Get all alarms for this device
        $alarms = $alarm->getByDeviceId($deviceData['device_id']);
        
        foreach ($alarms as $alarmData) {
            if (!$alarmData['enabled']) {
                continue;
            }
            
            $alarmTime = new DateTime($alarmData['time']);
            $days = json_decode($alarmData['days'], true);
            
            // If no specific days are set, treat as one-time alarm
            if (empty($days)) {
                if ($alarmTime > $now && (!$nextAlarm || $alarmTime < $nextAlarm['time'])) {
                    $nextAlarm = [
                        'time' => $alarmTime,
                        'device' => $deviceData,
                        'alarm' => $alarmData
                    ];
                }
                continue;
            }
            
            // Check recurring alarms
            $today = (int)$now->format('w');
            $todayTime = clone $alarmTime;
            $todayTime->setDate($now->format('Y'), $now->format('m'), $now->format('d'));
            
            // Check today and next 7 days
            for ($i = 0; $i < 8; $i++) {
                $checkDay = ($today + $i) % 7;
                if (in_array($checkDay, $days)) {
                    $checkTime = clone $todayTime;
                    if ($i > 0) {
                        $checkTime->modify("+{$i} days");
                    }
                    
                    if ($checkTime > $now && (!$nextAlarm || $checkTime < $nextAlarm['time'])) {
                        $nextAlarm = [
                            'time' => $checkTime,
                            'device' => $deviceData,
                            'alarm' => $alarmData
                        ];
                        break;
                    }
                }
            }
        }
    }
    
    if ($nextAlarm) {
        echo json_encode([
            'has_next_alarm' => true,
            'next_alarm' => [
                'time' => $nextAlarm['time']->format('Y-m-d H:i:s'),
                'device_name' => $nextAlarm['device']['name'],
                'device_id' => $nextAlarm['device']['device_id'],
                'alarm_label' => $nextAlarm['alarm']['label'],
                'alarm_id' => $nextAlarm['alarm']['id'],
                'sound' => $nextAlarm['alarm']['sound'],
                'rgb_enabled' => $nextAlarm['alarm']['rgb_enabled'],
                'rgb_color' => $nextAlarm['alarm']['rgb_color']
            ]
        ]);
    } else {
        echo json_encode([
            'has_next_alarm' => false
        ]);
    }

} catch (Exception $e) {
    http_response_code(400);
    echo json_encode(['error' => $e->getMessage()]);
} 