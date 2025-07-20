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

// Check if job ID is provided
if (!isset($_POST['job_id']) || !is_numeric($_POST['job_id'])) {
    $response['message'] = 'Invalid job ID';
    echo json_encode($response);
    exit;
}

try {
    $job_id = $_POST['job_id'];
    
    // Start transaction
    mysqli_begin_transaction($conn);
    
    // First, delete all applications for this job
    $delete_applications = "DELETE FROM applications WHERE job_id = ?";
    $stmt = mysqli_prepare($conn, $delete_applications);
    if (!$stmt) {
        throw new Exception("Error preparing applications deletion: " . mysqli_error($conn));
    }
    mysqli_stmt_bind_param($stmt, "i", $job_id);
    mysqli_stmt_execute($stmt);
    
    // Then delete the job
    $delete_job = "DELETE FROM jobs WHERE id = ?";
    $stmt = mysqli_prepare($conn, $delete_job);
    if (!$stmt) {
        throw new Exception("Error preparing job deletion: " . mysqli_error($conn));
    }
    mysqli_stmt_bind_param($stmt, "i", $job_id);
    
    if (mysqli_stmt_execute($stmt)) {
        mysqli_commit($conn);
        $response['success'] = true;
        $response['message'] = "Job and all associated applications have been deleted successfully";
    } else {
        throw new Exception("Error deleting job: " . mysqli_stmt_error($stmt));
    }
    
} catch (Exception $e) {
    mysqli_rollback($conn);
    $response['message'] = $e->getMessage();
}

// Return JSON response
header('Content-Type: application/json');
echo json_encode($response); 