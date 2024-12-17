<?php
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../utils/Auth.php';
require_once __DIR__ . '/../../models/Calendar.php';

header('Content-Type: application/json');

try {
    // Verify authentication
    $userData = Auth::requireAuth();
    
    $calendar = new Calendar();
    
    // Check if calendar is integrated
    if (!$calendar->hasValidIntegration($userData['user_id'])) {
        echo json_encode([
            'integrated' => false,
            'auth_url' => $calendar->getAuthUrl()
        ]);
        exit;
    }

    // Get number of days to look ahead
    $days = isset($_GET['days']) ? min(30, max(1, intval($_GET['days']))) : 7;

    // Get upcoming events
    $events = $calendar->getUpcomingEvents($userData['user_id'], $days);

    if ($events === false) {
        throw new Exception('Failed to fetch calendar events');
    }

    echo json_encode([
        'integrated' => true,
        'events' => $events
    ]);

} catch (Exception $e) {
    http_response_code(400);
    echo json_encode(['error' => $e->getMessage()]);
} 