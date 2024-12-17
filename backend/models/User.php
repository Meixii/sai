<?php
require_once __DIR__ . '/../utils/Database.php';

class User {
    private $db;

    public function __construct() {
        $this->db = Database::getInstance();
    }

    /**
     * Create a new user
     * @param array $userData The user data
     * @return int|false The new user ID or false on failure
     */
    public function create($userData) {
        try {
            $this->db->beginTransaction();

            $query = "INSERT INTO users (email, password_hash, name, created_at) 
                     VALUES (:email, :password_hash, :name, NOW())";
            
            $params = [
                'email' => $userData['email'],
                'password_hash' => password_hash($userData['password'], PASSWORD_DEFAULT),
                'name' => $userData['name']
            ];

            $this->db->query($query, $params);
            $userId = $this->db->lastInsertId();

            $this->db->commit();
            return $userId;
        } catch (Exception $e) {
            $this->db->rollback();
            throw $e;
        }
    }

    /**
     * Create or update a user from Google OAuth data
     * @param array $googleData The Google user data
     * @return array The user data
     */
    public function createOrUpdateFromGoogle($googleData) {
        try {
            $this->db->beginTransaction();

            $query = "SELECT * FROM users WHERE google_id = :google_id OR email = :email";
            $user = $this->db->getRow($query, [
                'google_id' => $googleData['sub'],
                'email' => $googleData['email']
            ]);

            if ($user) {
                // Update existing user
                $query = "UPDATE users SET 
                         google_id = :google_id,
                         name = :name,
                         picture = :picture,
                         updated_at = NOW()
                         WHERE id = :id";
                
                $this->db->query($query, [
                    'google_id' => $googleData['sub'],
                    'name' => $googleData['name'],
                    'picture' => $googleData['picture'],
                    'id' => $user['id']
                ]);
            } else {
                // Create new user
                $query = "INSERT INTO users 
                         (google_id, email, name, picture, created_at) 
                         VALUES 
                         (:google_id, :email, :name, :picture, NOW())";
                
                $this->db->query($query, [
                    'google_id' => $googleData['sub'],
                    'email' => $googleData['email'],
                    'name' => $googleData['name'],
                    'picture' => $googleData['picture']
                ]);
                
                $user = $this->getByEmail($googleData['email']);
            }

            $this->db->commit();
            return $user;
        } catch (Exception $e) {
            $this->db->rollback();
            throw $e;
        }
    }

    /**
     * Get a user by email
     * @param string $email The user's email
     * @return array|false The user data or false if not found
     */
    public function getByEmail($email) {
        $query = "SELECT * FROM users WHERE email = :email";
        return $this->db->getRow($query, ['email' => $email]);
    }

    /**
     * Get a user by ID
     * @param int $id The user ID
     * @return array|false The user data or false if not found
     */
    public function getById($id) {
        $query = "SELECT * FROM users WHERE id = :id";
        return $this->db->getRow($query, ['id' => $id]);
    }

    /**
     * Verify user's password
     * @param string $email The user's email
     * @param string $password The password to verify
     * @return array|false The user data if verified, false otherwise
     */
    public function verifyPassword($email, $password) {
        $user = $this->getByEmail($email);
        if (!$user || !password_verify($password, $user['password_hash'])) {
            return false;
        }
        return $user;
    }

    /**
     * Update user's password
     * @param int $userId The user ID
     * @param string $newPassword The new password
     * @return bool Success status
     */
    public function updatePassword($userId, $newPassword) {
        $query = "UPDATE users SET 
                 password_hash = :password_hash,
                 updated_at = NOW()
                 WHERE id = :id";
        
        return $this->db->query($query, [
            'password_hash' => password_hash($newPassword, PASSWORD_DEFAULT),
            'id' => $userId
        ]) !== false;
    }

    /**
     * Update user's profile
     * @param int $userId The user ID
     * @param array $profileData The profile data to update
     * @return bool Success status
     */
    public function updateProfile($userId, $profileData) {
        $query = "UPDATE users SET 
                 name = :name,
                 updated_at = NOW()
                 WHERE id = :id";
        
        return $this->db->query($query, [
            'name' => $profileData['name'],
            'id' => $userId
        ]) !== false;
    }

    /**
     * Update user's last login timestamp
     * @param int $userId The user ID
     * @return bool Success status
     */
    public function updateLastLogin($userId) {
        $query = "UPDATE users SET 
                 last_login = NOW(),
                 updated_at = NOW()
                 WHERE id = :id";
        
        return $this->db->query($query, [
            'id' => $userId
        ]) !== false;
    }

    /**
     * Update user's verification token
     * @param int $userId The user ID
     * @param string $token The verification token
     * @return bool Success status
     */
    public function updateVerificationToken($userId, $token) {
        $query = "UPDATE users SET 
                 verification_token = :token,
                 updated_at = NOW()
                 WHERE id = :id";
        
        return $this->db->query($query, [
            'token' => $token,
            'id' => $userId
        ]) !== false;
    }

    /**
     * Get user by verification token
     * @param string $token The verification token
     * @return array|false The user data or false if not found
     */
    public function getByVerificationToken($token) {
        $query = "SELECT * FROM users WHERE verification_token = :token";
        return $this->db->getRow($query, ['token' => $token]);
    }

    /**
     * Verify user's email
     * @param int $userId The user ID
     * @return bool Success status
     */
    public function verifyEmail($userId) {
        $query = "UPDATE users SET 
                 email_verified = 1,
                 verification_token = NULL,
                 updated_at = NOW()
                 WHERE id = :id";
        
        return $this->db->query($query, [
            'id' => $userId
        ]) !== false;
    }
} 