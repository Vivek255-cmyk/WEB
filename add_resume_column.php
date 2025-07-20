<?php
require_once '../config.php';

try {
    // Add resume column to student_profiles table
    $sql = "ALTER TABLE student_profiles ADD COLUMN resume VARCHAR(255) DEFAULT NULL";
    
    if (mysqli_query($conn, $sql)) {
        echo "Resume column added successfully to student_profiles table.";
    } else {
        echo "Error adding resume column: " . mysqli_error($conn);
    }
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
?> 