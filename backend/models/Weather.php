<?php
require_once __DIR__ . '/../utils/Database.php';

class Weather {
    private $api_key;
    private $cache_duration = 1800; // 30 minutes
    private $db;
    
    public function __construct() {
        global $config;
        $this->api_key = $config['weather_api_key'];
        $this->db = Database::getInstance();
    }
    
    public function getCurrentWeather($location) {
        if ($location === 'auto:ip') {
            $location = $this->getLocationFromIP();
        }
        
        $url = "http://api.weatherapi.com/v1/current.json";
        $params = [
            'key' => $this->api_key,
            'q' => $location,
            'aqi' => 'no'
        ];
        
        $response = $this->makeApiRequest($url, $params);
        if ($response) {
            // Cache the response
            $this->cacheWeatherData($location, $response);
            return $response;
        }
        
        throw new Exception("Failed to fetch weather data");
    }
    
    public function getCachedWeather($location) {
        if ($location === 'auto:ip') {
            $location = $this->getLocationFromIP();
        }
        
        $query = "SELECT data, created_at FROM weather_cache WHERE location = :location";
        $result = $this->db->getRow($query, ['location' => $location]);
        
        if ($result && (time() - strtotime($result['created_at'])) < $this->cache_duration) {
            return json_decode($result['data'], true);
        }
        
        return null;
    }
    
    private function cacheWeatherData($location, $data) {
        $query = "INSERT INTO weather_cache (location, data, created_at) 
                 VALUES (:location, :data, NOW()) 
                 ON DUPLICATE KEY UPDATE data = :data, created_at = NOW()";
                 
        $params = [
            'location' => $location,
            'data' => json_encode($data)
        ];
        
        $this->db->execute($query, $params);
    }
    
    private function getLocationFromIP() {
        $ip = $_SERVER['REMOTE_ADDR'];
        if ($ip === '127.0.0.1' || $ip === '::1') {
            // Default location for localhost
            return 'Manila';
        }
        
        // Use ip-api.com for IP geolocation (free, no API key required)
        $url = "http://ip-api.com/json/{$ip}";
        $response = file_get_contents($url);
        
        if ($response) {
            $data = json_decode($response, true);
            if ($data && $data['status'] === 'success') {
                return $data['city'] . ',' . $data['country'];
            }
        }
        
        // Default fallback
        return 'Manila';
    }
    
    private function makeApiRequest($url, $params) {
        $url .= '?' . http_build_query($params);
        
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        
        curl_close($ch);
        
        if ($httpCode === 200 && $response) {
            return json_decode($response, true);
        }
        
        return null;
    }
} 