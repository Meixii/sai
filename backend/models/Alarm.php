<?php
require_once __DIR__ . '/../utils/Database.php';

class Alarm {
    private $db;

    public function __construct() {
        $this->db = Database::getInstance();
    }

    /**
     * Create a new alarm
     * @param array $alarmData The alarm data
     * @return int|false The new alarm ID or false on failure
     */
    public function create($alarmData) {
        try {
            $this->db->beginTransaction();

            $query = "INSERT INTO alarms 
                     (device_id, time, label, days, enabled, sound, rgb_enabled, rgb_color, created_at) 
                     VALUES 
                     (:device_id, :time, :label, :days, :enabled, :sound, :rgb_enabled, :rgb_color, NOW())";
            
            $this->db->query($query, [
                'device_id' => $alarmData['device_id'],
                'time' => $alarmData['time'],
                'label' => $alarmData['label'] ?? '',
                'days' => json_encode($alarmData['days'] ?? []),
                'enabled' => $alarmData['enabled'] ?? true,
                'sound' => $alarmData['sound'] ?? 'default.mp3',
                'rgb_enabled' => $alarmData['rgb_enabled'] ?? true,
                'rgb_color' => $alarmData['rgb_color'] ?? '#FF0000'
            ]);

            $alarmId = $this->db->lastInsertId();
            $this->db->commit();
            return $alarmId;
        } catch (Exception $e) {
            $this->db->rollback();
            throw $e;
        }
    }

    /**
     * Update an alarm
     * @param int $alarmId The alarm ID
     * @param array $alarmData The alarm data to update
     * @return bool Success status
     */
    public function update($alarmId, $alarmData) {
        try {
            $this->db->beginTransaction();

            $query = "UPDATE alarms SET 
                     time = :time,
                     label = :label,
                     days = :days,
                     enabled = :enabled,
                     sound = :sound,
                     rgb_enabled = :rgb_enabled,
                     rgb_color = :rgb_color,
                     updated_at = NOW()
                     WHERE id = :id";
            
            $this->db->query($query, array_merge($alarmData, ['id' => $alarmId]));

            $this->db->commit();
            return true;
        } catch (Exception $e) {
            $this->db->rollback();
            throw $e;
        }
    }

    /**
     * Delete an alarm
     * @param int $alarmId The alarm ID
     * @return bool Success status
     */
    public function delete($alarmId) {
        return $this->db->query(
            "DELETE FROM alarms WHERE id = :id",
            ['id' => $alarmId]
        ) !== false;
    }

    /**
     * Get alarm by ID
     * @param int $alarmId The alarm ID
     * @return array|false The alarm data or false if not found
     */
    public function getById($alarmId) {
        $alarm = $this->db->getRow(
            "SELECT * FROM alarms WHERE id = :id",
            ['id' => $alarmId]
        );

        if ($alarm) {
            $alarm['days'] = json_decode($alarm['days'], true);
        }

        return $alarm;
    }

    /**
     * Get alarms by device ID
     * @param string $deviceId The device ID
     * @return array The alarms
     */
    public function getByDeviceId($deviceId) {
        $alarms = $this->db->getRows(
            "SELECT * FROM alarms 
             WHERE device_id = :device_id 
             ORDER BY time ASC",
            ['device_id' => $deviceId]
        );

        foreach ($alarms as &$alarm) {
            $alarm['days'] = json_decode($alarm['days'], true);
        }

        return $alarms;
    }

    /**
     * Get next alarm for device
     * @param string $deviceId The device ID
     * @return array|false The next alarm or false if none found
     */
    public function getNextAlarm($deviceId) {
        $now = new DateTime();
        $currentDay = strtolower($now->format('D'));
        $currentTime = $now->format('H:i:s');

        $alarms = $this->getByDeviceId($deviceId);
        $nextAlarm = null;
        $shortestDiff = PHP_INT_MAX;

        foreach ($alarms as $alarm) {
            if (!$alarm['enabled']) {
                continue;
            }

            $alarmTime = new DateTime($alarm['time']);
            $alarmDays = $alarm['days'];

            // If no specific days are set, alarm runs daily
            if (empty($alarmDays)) {
                $diff = $this->calculateTimeDifference($currentTime, $alarm['time']);
                if ($diff < $shortestDiff) {
                    $shortestDiff = $diff;
                    $nextAlarm = $alarm;
                }
                continue;
            }

            // Check each day the alarm is set for
            foreach ($alarmDays as $day) {
                $diff = $this->calculateDayTimeDifference($currentDay, $day, $currentTime, $alarm['time']);
                if ($diff < $shortestDiff) {
                    $shortestDiff = $diff;
                    $nextAlarm = $alarm;
                }
            }
        }

        return $nextAlarm;
    }

    /**
     * Calculate time difference in minutes
     * @param string $currentTime Current time (HH:mm:ss)
     * @param string $alarmTime Alarm time (HH:mm:ss)
     * @return int Difference in minutes
     */
    private function calculateTimeDifference($currentTime, $alarmTime) {
        $current = new DateTime($currentTime);
        $alarm = new DateTime($alarmTime);
        
        $diff = $alarm->getTimestamp() - $current->getTimestamp();
        if ($diff < 0) {
            $diff += 86400; // Add 24 hours if alarm is for next day
        }
        
        return $diff / 60;
    }

    /**
     * Calculate day and time difference in minutes
     * @param string $currentDay Current day (3-letter format)
     * @param string $alarmDay Alarm day (3-letter format)
     * @param string $currentTime Current time (HH:mm:ss)
     * @param string $alarmTime Alarm time (HH:mm:ss)
     * @return int Difference in minutes
     */
    private function calculateDayTimeDifference($currentDay, $alarmDay, $currentTime, $alarmTime) {
        $days = ['mon', 'tue', 'wed', 'thu', 'fri', 'sat', 'sun'];
        $currentDayIndex = array_search(strtolower($currentDay), $days);
        $alarmDayIndex = array_search(strtolower($alarmDay), $days);
        
        $dayDiff = $alarmDayIndex - $currentDayIndex;
        if ($dayDiff < 0) {
            $dayDiff += 7;
        }
        
        $timeDiff = $this->calculateTimeDifference($currentTime, $alarmTime);
        if ($dayDiff === 0 && $timeDiff < 0) {
            $dayDiff = 7;
        }
        
        return ($dayDiff * 1440) + $timeDiff; // 1440 = minutes in a day
    }

    /**
     * Toggle alarm enabled status
     * @param int $alarmId The alarm ID
     * @return bool Success status
     */
    public function toggleEnabled($alarmId) {
        return $this->db->query(
            "UPDATE alarms 
             SET enabled = NOT enabled,
             updated_at = NOW()
             WHERE id = :id",
            ['id' => $alarmId]
        ) !== false;
    }
} 