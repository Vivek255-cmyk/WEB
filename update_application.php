<?php
session_start();
require_once '../config.php';

// Check if user is logged in and is a company
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] != 'company') {
    header("Location: ../index.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['application_id']) && isset($_POST['status'])) {
    $application_id = (int)$_POST['application_id'];
    $status = $_POST['status'];
    
    // Validate status
    if (!in_array($status, ['accepted', 'rejected'])) {
        $_SESSION['error'] = "Invalid status value";
        header("Location: view_applications.php");
        exit();
    }
    
    // Verify that the application belongs to a job posted by this company
    $verify_query = "SELECT j.id 
                    FROM applications a 
                    JOIN jobs j ON a.job_id = j.id 
                    JOIN company_profiles cp ON j.company_id = cp.id 
                    WHERE a.id = ? AND cp.user_id = ?";
    $stmt = mysqli_prepare($conn, $verify_query);
    mysqli_stmt_bind_param($stmt, "ii", $application_id, $_SESSION['user_id']);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    
    if (mysqli_num_rows($result) > 0) {
        // Update application status
        $update_query = "UPDATE applications SET status = ? WHERE id = ?";
        $stmt = mysqli_prepare($conn, $update_query);
        mysqli_stmt_bind_param($stmt, "si", $status, $application_id);
        
        if (mysqli_stmt_execute($stmt)) {
            $_SESSION['success'] = "Application status updated successfully";
        } else {
            $_SESSION['error'] = "Error updating application status";
        }
    } else {
        $_SESSION['error'] = "Invalid application or unauthorized access";
    }
    
    header("Location: view_applications.php");
    exit();
} else {
    header("Location: view_applications.php");
    exit();
} 