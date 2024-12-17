<?php
require_once __DIR__ . '/../utils/Database.php';
require_once __DIR__ . '/../vendor/autoload.php';

class Calendar {
    private $db;
    private $client;

    public function __construct() {
        $this->db = Database::getInstance();
        $this->initializeGoogleClient();
    }

    /**
     * Initialize Google Client
     */
    private function initializeGoogleClient() {
        $this->client = new Google_Client();
        $this->client->setClientId(GOOGLE_CLIENT_ID);
        $this->client->setClientSecret(GOOGLE_CLIENT_SECRET);
        $this->client->setRedirectUri(APP_URL . '/api/calendar/callback.php');
        $this->client->setScopes([
            Google_Service_Calendar::CALENDAR_READONLY,
            Google_Service_Calendar::CALENDAR_EVENTS_READONLY
        ]);
    }

    /**
     * Get Google Calendar authorization URL
     * @return string Authorization URL
     */
    public function getAuthUrl() {
        return $this->client->createAuthUrl();
    }

    /**
     * Handle OAuth callback and store credentials
     * @param string $code Authorization code
     * @param int $userId User ID
     * @return bool Success status
     */
    public function handleCallback($code, $userId) {
        try {
            $token = $this->client->fetchAccessTokenWithAuthCode($code);
            
            if (isset($token['error'])) {
                throw new Exception($token['error_description']);
            }

            $this->storeCredentials($userId, $token);
            return true;
        } catch (Exception $e) {
            error_log("Calendar auth error: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Store Google Calendar credentials
     * @param int $userId User ID
     * @param array $token Access token data
     */
    private function storeCredentials($userId, $token) {
        $query = "INSERT INTO calendar_credentials 
                 (user_id, access_token, refresh_token, expires_at, created_at) 
                 VALUES 
                 (:user_id, :access_token, :refresh_token, :expires_at, NOW())
                 ON DUPLICATE KEY UPDATE 
                 access_token = VALUES(access_token),
                 refresh_token = VALUES(refresh_token),
                 expires_at = VALUES(expires_at)";

        $this->db->query($query, [
            'user_id' => $userId,
            'access_token' => $token['access_token'],
            'refresh_token' => $token['refresh_token'] ?? null,
            'expires_at' => date('Y-m-d H:i:s', time() + $token['expires_in'])
        ]);
    }

    /**
     * Load credentials for user
     * @param int $userId User ID
     * @return bool Whether credentials were loaded successfully
     */
    private function loadCredentials($userId) {
        $credentials = $this->db->getRow(
            "SELECT * FROM calendar_credentials WHERE user_id = :user_id",
            ['user_id' => $userId]
        );

        if (!$credentials) {
            return false;
        }

        $token = [
            'access_token' => $credentials['access_token'],
            'refresh_token' => $credentials['refresh_token'],
            'expires_in' => strtotime($credentials['expires_at']) - time()
        ];

        $this->client->setAccessToken($token);

        if ($this->client->isAccessTokenExpired()) {
            if (!$this->refreshToken($userId)) {
                return false;
            }
        }

        return true;
    }

    /**
     * Refresh access token
     * @param int $userId User ID
     * @return bool Success status
     */
    private function refreshToken($userId) {
        try {
            $token = $this->client->fetchAccessTokenWithRefreshToken();
            $this->storeCredentials($userId, $token);
            return true;
        } catch (Exception $e) {
            error_log("Token refresh error: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Get upcoming events
     * @param int $userId User ID
     * @param int $days Number of days to look ahead
     * @return array|false Events or false on failure
     */
    public function getUpcomingEvents($userId, $days = 7) {
        if (!$this->loadCredentials($userId)) {
            return false;
        }

        try {
            $service = new Google_Service_Calendar($this->client);
            
            $timeMin = new DateTime();
            $timeMax = new DateTime();
            $timeMax->modify("+{$days} days");

            $optParams = [
                'maxResults' => 10,
                'orderBy' => 'startTime',
                'singleEvents' => true,
                'timeMin' => $timeMin->format(DateTime::RFC3339),
                'timeMax' => $timeMax->format(DateTime::RFC3339)
            ];

            $results = $service->events->listEvents('primary', $optParams);
            return $this->formatEvents($results->getItems());
        } catch (Exception $e) {
            error_log("Calendar events error: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Format calendar events
     * @param array $events Raw event data
     * @return array Formatted events
     */
    private function formatEvents($events) {
        $formatted = [];
        
        foreach ($events as $event) {
            $start = $event->start->dateTime;
            if (empty($start)) {
                $start = $event->start->date;
            }

            $end = $event->end->dateTime;
            if (empty($end)) {
                $end = $event->end->date;
            }

            $formatted[] = [
                'id' => $event->id,
                'title' => $event->summary,
                'description' => $event->description,
                'start' => $start,
                'end' => $end,
                'location' => $event->location,
                'htmlLink' => $event->htmlLink,
                'status' => $event->status,
                'created' => $event->created,
                'updated' => $event->updated
            ];
        }

        return $formatted;
    }

    /**
     * Check if user has valid calendar integration
     * @param int $userId User ID
     * @return bool Whether user has valid calendar integration
     */
    public function hasValidIntegration($userId) {
        return $this->loadCredentials($userId);
    }

    /**
     * Remove calendar integration
     * @param int $userId User ID
     * @return bool Success status
     */
    public function removeIntegration($userId) {
        return $this->db->query(
            "DELETE FROM calendar_credentials WHERE user_id = :user_id",
            ['user_id' => $userId]
        ) !== false;
    }
} 