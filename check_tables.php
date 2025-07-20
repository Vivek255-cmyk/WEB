<?php
require_once '../config.php';

try {
    // Check if tables exist
    echo "<h3>Checking if tables exist:</h3>";
    $tables = ['users', 'student_profiles', 'applications'];
    foreach ($tables as $table) {
        $result = $conn->query("SHOW TABLES LIKE '$table'");
        echo "$table exists: " . ($result->num_rows > 0 ? "Yes" : "No") . "<br>";
    }
    echo "<br>";

    // Check student_profiles table structure
    echo "<h3>student_profiles table structure:</h3>";
    $result = $conn->query("DESCRIBE student_profiles");
    if ($result === false) {
        echo "Error describing student_profiles table: " . $conn->error . "<br>";
    } else {
        $columns = $result->fetch_all(MYSQLI_ASSOC);
        echo "<pre>";
        print_r($columns);
        echo "</pre>";
    }

    // Check users table structure
    echo "<h3>users table structure:</h3>";
    $result = $conn->query("DESCRIBE users");
    if ($result === false) {
        echo "Error describing users table: " . $conn->error . "<br>";
    } else {
        $columns = $result->fetch_all(MYSQLI_ASSOC);
        echo "<pre>";
        print_r($columns);
        echo "</pre>";
    }

    // Show sample data from student_profiles
    echo "<h3>Sample data from student_profiles:</h3>";
    $result = $conn->query("SELECT * FROM student_profiles LIMIT 1");
    if ($result === false) {
        echo "Error fetching sample data: " . $conn->error . "<br>";
    } else {
        $sample = $result->fetch_assoc();
        echo "<pre>";
        print_r($sample);
        echo "</pre>";
    }

} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
?> 