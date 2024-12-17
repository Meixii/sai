<?php
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../utils/Auth.php';
require_once __DIR__ . '/../../models/Weather.php';

header('Content-Type: application/json');

try {
    // Verify authentication
    $userData = Auth::requireAuth();
    
    $weather = new Weather();
    
    // Get location from query string or default to auto:ip
    $location = $_GET['location'] ?? 'auto:ip';

    // Try to get cached weather data first
    $weatherData = $weather->getCachedWeather($location);

    if (!$weatherData) {
        // If no cached data or expired, fetch new data
        $weatherData = $weather->getCurrentWeather($location);
    }

    // Get forecast if requested
    if (isset($_GET['forecast']) && $_GET['forecast'] === 'true') {
        $days = isset($_GET['days']) ? min(7, max(1, intval($_GET['days']))) : 3;
        $forecast = $weather->getForecast($location, $days);
        $weatherData['forecast'] = $forecast['forecast'];
    }

    // Get alerts if requested
    if (isset($_GET['alerts']) && $_GET['alerts'] === 'true') {
        $alerts = $weather->getAlerts($location);
        $weatherData['alerts'] = $alerts;
    }

    echo json_encode($weatherData);

} catch (Exception $e) {
    http_response_code(400);
    echo json_encode(['error' => $e->getMessage()]);
} 