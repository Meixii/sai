<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../utils/Database.php';

try {
    $db = Database::getInstance();
    
    // Create weather_cache table if it doesn't exist
    $query = "CREATE TABLE IF NOT EXISTS weather_cache (
        id INT AUTO_INCREMENT PRIMARY KEY,
        location VARCHAR(255) NOT NULL,
        data TEXT NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        UNIQUE KEY unique_location (location)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;";
    
    $db->execute($query);
    echo "Weather cache table created successfully!\n";
    
} catch (Exception $e) {
    echo "Error creating weather cache table: " . $e->getMessage() . "\n";
    exit(1);
} 