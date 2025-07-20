<?php
session_start();
require_once '../config.php';

// Initialize response array
$response = [
    'success' => false,
    'message' => ''
];

// Check if user is logged in and is an admin
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'admin') {
    $response['message'] = 'Unauthorized access';
    echo json_encode($response);
    exit;
}

// Check if company ID is provided
if (!isset($_POST['company_id']) || !is_numeric($_POST['company_id'])) {
    $response['message'] = 'Invalid company ID';
    echo json_encode($response);
    exit;
}

try {
    $company_id = $_POST['company_id'];
    
    // Start transaction
    mysqli_begin_transaction($conn);
    
    // First, delete all applications for this company's jobs
    $delete_applications = "DELETE a FROM applications a 
                          JOIN jobs j ON a.job_id = j.id 
                          WHERE j.company_id = ?";
    $stmt = mysqli_prepare($conn, $delete_applications);
    if (!$stmt) {
        throw new Exception("Error preparing applications deletion: " . mysqli_error($conn));
    }
    mysqli_stmt_bind_param($stmt, "i", $company_id);
    mysqli_stmt_execute($stmt);
    
    // Then delete all jobs
    $delete_jobs = "DELETE FROM jobs WHERE company_id = ?";
    $stmt = mysqli_prepare($conn, $delete_jobs);
    if (!$stmt) {
        throw new Exception("Error preparing jobs deletion: " . mysqli_error($conn));
    }
    mysqli_stmt_bind_param($stmt, "i", $company_id);
    mysqli_stmt_execute($stmt);
    
    // Get user_id before deleting company profile
    $get_user_id = "SELECT user_id FROM company_profiles WHERE id = ?";
    $stmt = mysqli_prepare($conn, $get_user_id);
    if (!$stmt) {
        throw new Exception("Error preparing user_id query: " . mysqli_error($conn));
    }
    mysqli_stmt_bind_param($stmt, "i", $company_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $company = mysqli_fetch_assoc($result);
    $user_id = $company['user_id'];
    
    // Delete company profile
    $delete_company = "DELETE FROM company_profiles WHERE id = ?";
    $stmt = mysqli_prepare($conn, $delete_company);
    if (!$stmt) {
        throw new Exception("Error preparing company deletion: " . mysqli_error($conn));
    }
    mysqli_stmt_bind_param($stmt, "i", $company_id);
    
    if (mysqli_stmt_execute($stmt)) {
        // Delete user account
        $delete_user = "DELETE FROM users WHERE id = ?";
        $stmt = mysqli_prepare($conn, $delete_user);
        if (!$stmt) {
            throw new Exception("Error preparing user deletion: " . mysqli_error($conn));
        }
        mysqli_stmt_bind_param($stmt, "i", $user_id);
        mysqli_stmt_execute($stmt);
        
        mysqli_commit($conn);
        $response['success'] = true;
        $response['message'] = "Company and all associated data have been deleted successfully";
    } else {
        throw new Exception("Error deleting company: " . mysqli_stmt_error($stmt));
    }
    
} catch (Exception $e) {
    mysqli_rollback($conn);
    $response['message'] = $e->getMessage();
}

// Return JSON response
header('Content-Type: application/json');
echo json_encode($response); 