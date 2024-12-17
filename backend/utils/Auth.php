<?php
require_once __DIR__ . '/../config/config.php';

class Auth {
    /**
     * Generate a JWT token
     * @param array $payload The data to encode in the token
     * @return string The JWT token
     */
    public static function generateToken($payload) {
        $header = json_encode([
            'typ' => 'JWT',
            'alg' => 'HS256'
        ]);

        $payload['exp'] = time() + TOKEN_EXPIRY;
        $payload = json_encode($payload);

        $base64UrlHeader = str_replace(['+', '/', '='], ['-', '_', ''], base64_encode($header));
        $base64UrlPayload = str_replace(['+', '/', '='], ['-', '_', ''], base64_encode($payload));

        $signature = hash_hmac('sha256', $base64UrlHeader . "." . $base64UrlPayload, JWT_SECRET, true);
        $base64UrlSignature = str_replace(['+', '/', '='], ['-', '_', ''], base64_encode($signature));

        return $base64UrlHeader . "." . $base64UrlPayload . "." . $base64UrlSignature;
    }

    /**
     * Validate a JWT token
     * @param string $token The JWT token to validate
     * @return array|false The decoded payload if valid, false otherwise
     */
    public static function validateToken($token) {
        $parts = explode('.', $token);
        if (count($parts) != 3) {
            return false;
        }

        list($base64UrlHeader, $base64UrlPayload, $base64UrlSignature) = $parts;

        $signature = base64_decode(str_replace(['-', '_'], ['+', '/'], $base64UrlSignature));
        $expectedSignature = hash_hmac('sha256', $base64UrlHeader . "." . $base64UrlPayload, JWT_SECRET, true);

        if (!hash_equals($signature, $expectedSignature)) {
            return false;
        }

        $payload = json_decode(base64_decode(str_replace(['-', '_'], ['+', '/'], $base64UrlPayload)), true);

        if (!$payload || !isset($payload['exp']) || $payload['exp'] < time()) {
            return false;
        }

        return $payload;
    }

    /**
     * Get the Bearer token from the Authorization header
     * @return string|false The token if found, false otherwise
     */
    public static function getBearerToken() {
        $headers = apache_request_headers();
        if (!isset($headers['Authorization'])) {
            return false;
        }

        if (preg_match('/Bearer\s(\S+)/', $headers['Authorization'], $matches)) {
            return $matches[1];
        }

        return false;
    }

    /**
     * Check if the current request is authenticated
     * @return array|false The user data if authenticated, false otherwise
     */
    public static function checkAuth() {
        $token = self::getBearerToken();
        if (!$token) {
            return false;
        }

        return self::validateToken($token);
    }

    /**
     * Require authentication for the current request
     * @return array The user data if authenticated
     * @throws Exception if not authenticated
     */
    public static function requireAuth() {
        $userData = self::checkAuth();
        if (!$userData) {
            http_response_code(401);
            throw new Exception('Unauthorized');
        }
        return $userData;
    }
} 