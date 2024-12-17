<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../utils/Database.php';

try {
    $db = Database::getInstance();

    // Default sounds
    $defaultSounds = [
        [
            'name' => 'Classic Beep',
            'filename' => 'classic-beep.mp3',
            'is_default' => true
        ],
        [
            'name' => 'Digital',
            'filename' => 'digital.mp3',
            'is_default' => true
        ],
        [
            'name' => 'Gentle Wake',
            'filename' => 'gentle-wake.mp3',
            'is_default' => true
        ]
    ];

    // First, clear existing default sounds
    $db->query("DELETE FROM alarm_sounds WHERE is_default = TRUE");

    // Insert default sounds
    $query = "INSERT INTO alarm_sounds (name, filename, is_default, created_at) 
              VALUES (:name, :filename, :is_default, NOW())";

    foreach ($defaultSounds as $sound) {
        // Check if sound file exists
        $soundPath = __DIR__ . '/../uploads/sounds/' . $sound['filename'];
        if (!file_exists($soundPath)) {
            echo "Warning: Sound file not found: {$sound['filename']}\n";
            continue;
        }

        $success = $db->query($query, [
            'name' => $sound['name'],
            'filename' => $sound['filename'],
            'is_default' => $sound['is_default']
        ]);

        if ($success) {
            echo "Imported sound: {$sound['name']}\n";
        } else {
            echo "Failed to import sound: {$sound['name']}\n";
        }
    }

    echo "\nSound import completed successfully!\n";

} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
} 