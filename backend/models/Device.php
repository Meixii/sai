<?php
require_once __DIR__ . '/../utils/Database.php';

class Device {
    private $db;

    public function __construct() {
        $this->db = Database::getInstance();
    }

    /**
     * Register a new device
     * @param array $deviceData The device data
     * @return string|false The device ID or false on failure
     */
    public function register($deviceData) {
        try {
            $this->db->beginTransaction();

            // Generate unique device ID
            $deviceId = $this->generateDeviceId();

            $query = "INSERT INTO devices 
                     (device_id, user_id, name, created_at) 
                     VALUES 
                     (:device_id, :user_id, :name, NOW())";
            
            $this->db->query($query, [
                'device_id' => $deviceId,
                'user_id' => $deviceData['user_id'],
                'name' => $deviceData['name'] ?? 'Smart Alarm'
            ]);

            // Create default settings for the device
            $this->createDefaultSettings($deviceId);

            $this->db->commit();
            return $deviceId;
        } catch (Exception $e) {
            $this->db->rollback();
            throw $e;
        }
    }

    /**
     * Generate a unique device ID
     * @return string The generated device ID
     */
    private function generateDeviceId() {
        do {
            $deviceId = strtoupper(substr(md5(uniqid(mt_rand(), true)), 0, 8));
            $exists = $this->db->getRow(
                "SELECT 1 FROM devices WHERE device_id = :device_id",
                ['device_id' => $deviceId]
            );
        } while ($exists);

        return $deviceId;
    }

    /**
     * Create default settings for a device
     * @param string $deviceId The device ID
     */
    private function createDefaultSettings($deviceId) {
        $defaultSettings = [
            'theme' => 'default',
            'display_format' => '24h',
            'date_format' => 'MM/DD/YYYY',
            'temperature_unit' => 'C',
            'rgb_enabled' => true,
            'rgb_color' => '#FF0000',
            'alarm_sound' => 'default.mp3'
        ];

        $query = "INSERT INTO device_settings 
                 (device_id, setting_key, setting_value) 
                 VALUES 
                 (:device_id, :key, :value)";

        foreach ($defaultSettings as $key => $value) {
            $this->db->query($query, [
                'device_id' => $deviceId,
                'key' => $key,
                'value' => is_bool($value) ? ($value ? '1' : '0') : $value
            ]);
        }
    }

    /**
     * Get device by ID
     * @param string $deviceId The device ID
     * @return array|false The device data or false if not found
     */
    public function getById($deviceId) {
        return $this->db->getRow(
            "SELECT * FROM devices WHERE device_id = :device_id",
            ['device_id' => $deviceId]
        );
    }

    /**
     * Get devices by user ID
     * @param int $userId The user ID
     * @return array The devices
     */
    public function getByUserId($userId) {
        return $this->db->getRows(
            "SELECT * FROM devices WHERE user_id = :user_id",
            ['user_id' => $userId]
        );
    }

    /**
     * Update device settings
     * @param string $deviceId The device ID
     * @param array $settings The settings to update
     * @return bool Success status
     */
    public function updateSettings($deviceId, $settings) {
        try {
            $this->db->beginTransaction();

            $query = "INSERT INTO device_settings 
                     (device_id, setting_key, setting_value) 
                     VALUES 
                     (:device_id, :key, :value)
                     ON DUPLICATE KEY UPDATE 
                     setting_value = VALUES(setting_value)";

            foreach ($settings as $key => $value) {
                $this->db->query($query, [
                    'device_id' => $deviceId,
                    'key' => $key,
                    'value' => is_bool($value) ? ($value ? '1' : '0') : $value
                ]);
            }

            $this->db->commit();
            return true;
        } catch (Exception $e) {
            $this->db->rollback();
            throw $e;
        }
    }

    /**
     * Get device settings
     * @param string $deviceId The device ID
     * @return array The device settings
     */
    public function getSettings($deviceId) {
        $settings = $this->db->getRows(
            "SELECT setting_key, setting_value 
             FROM device_settings 
             WHERE device_id = :device_id",
            ['device_id' => $deviceId]
        );

        $result = [];
        foreach ($settings as $setting) {
            $result[$setting['setting_key']] = $setting['setting_value'];
        }

        return $result;
    }

    /**
     * Update device last sync time
     * @param string $deviceId The device ID
     * @return bool Success status
     */
    public function updateLastSync($deviceId) {
        return $this->db->query(
            "UPDATE devices 
             SET last_sync = NOW() 
             WHERE device_id = :device_id",
            ['device_id' => $deviceId]
        ) !== false;
    }

    /**
     * Check if device exists and belongs to user
     * @param string $deviceId The device ID
     * @param int $userId The user ID
     * @return bool Whether the device exists and belongs to the user
     */
    public function validateOwnership($deviceId, $userId) {
        $device = $this->db->getRow(
            "SELECT 1 FROM devices 
             WHERE device_id = :device_id 
             AND user_id = :user_id",
            [
                'device_id' => $deviceId,
                'user_id' => $userId
            ]
        );

        return $device !== false;
    }
} 