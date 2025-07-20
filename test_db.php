<?php
session_start();
require_once '../config.php';

// Check if user is logged in and is an admin
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'admin') {
    die("Access denied. Admin privileges required.");
}

// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Database configuration
$host = "localhost";
$username = "root";
$password = "";
$database = "job_portal";

// Create connection
$conn = mysqli_connect($host, $username, $password, $database);

// Check connection
if (!$conn) {
    die("Connection failed: " . mysqli_connect_error());
}
echo "Connected successfully to database.\n";

echo "<pre>\n";

try {
    // Check if student_profiles table exists
    $check_table = "SHOW TABLES LIKE 'student_profiles'";
    $result = mysqli_query($conn, $check_table);
    
    if (!$result) {
        die("Error checking table: " . mysqli_error($conn));
    }
    
    if (mysqli_num_rows($result) == 0) {
        die("student_profiles table does not exist!");
    }
    
    echo "Found student_profiles table.\n";

    // Check if resume column exists
    $check_column = "SHOW COLUMNS FROM student_profiles LIKE 'resume'";
    $result = mysqli_query($conn, $check_column);
    
    if (!$result) {
        die("Error checking column: " . mysqli_error($conn));
    }
    
    if (mysqli_num_rows($result) == 0) {
        // Add resume column if it doesn't exist
        $add_column = "ALTER TABLE student_profiles ADD COLUMN resume VARCHAR(255) DEFAULT NULL";
        if (mysqli_query($conn, $add_column)) {
            echo "Resume column added successfully!\n";
        } else {
            echo "Error adding resume column: " . mysqli_error($conn) . "\n";
        }
    } else {
        echo "Resume column already exists.\n";
    }

    // Show current table structure
    echo "\nCurrent table structure:\n";
    echo "------------------------\n";
    $show_columns = "SHOW COLUMNS FROM student_profiles";
    $result = mysqli_query($conn, $show_columns);
    while ($row = mysqli_fetch_assoc($result)) {
        echo $row['Field'] . " - " . $row['Type'] . "\n";
    }

} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
} finally {
    mysqli_close($conn);
}

echo "</pre>";
?> 