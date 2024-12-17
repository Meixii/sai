<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../utils/Auth.php';
require_once __DIR__ . '/../models/Weather.php';

header('Content-Type: application/json');

try {
    // Verify authentication
    $userData = Auth::requireAuth();
    
    // Get weather instance
    $weather = new Weather();
    
    // Get location from request or default to auto:ip
    $location = $_GET['location'] ?? 'auto:ip';
    
    // Try to get cached weather first
    $weatherData = $weather->getCachedWeather($location);
    
    // If no cached data or cache expired, fetch new data
    if (!$weatherData) {
        $weatherData = $weather->getCurrentWeather($location);
    }
    
    // Check if we have valid weather data
    if (!$weatherData || !isset($weatherData['current'])) {
        throw new Exception("Unable to fetch weather data");
    }
    
    // Return weather data with null checks
    echo json_encode([
        'current' => [
            'temp_c' => $weatherData['current']['temp_c'] ?? null,
            'temp_f' => $weatherData['current']['temp_f'] ?? null,
            'condition' => $weatherData['current']['condition'] ?? ['text' => 'Unknown', 'icon' => ''],
            'humidity' => $weatherData['current']['humidity'] ?? null,
            'wind_kph' => $weatherData['current']['wind_kph'] ?? null,
            'wind_mph' => $weatherData['current']['wind_mph'] ?? null,
            'last_updated' => $weatherData['current']['last_updated'] ?? date('Y-m-d H:i:s')
        ],
        'location' => $weatherData['location'] ?? ['name' => $location]
    ]);

} catch (Exception $e) {
    error_log("Weather API Error: " . $e->getMessage());
    http_response_code(400);
    echo json_encode([
        'error' => $e->getMessage(),
        'current' => [
            'temp_c' => null,
            'temp_f' => null,
            'condition' => ['text' => 'Unknown', 'icon' => ''],
            'humidity' => null,
            'wind_kph' => null,
            'wind_mph' => null,
            'last_updated' => date('Y-m-d H:i:s')
        ],
        'location' => ['name' => 'Unknown']
    ]);
} 