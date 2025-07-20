<?php
session_start();
require_once '../config.php';

// Initialize response array
$response = [
    'success' => false,
    'message' => '',
    'new_status' => ''
];

// Check if user is logged in and is an admin
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'admin') {
    $response['message'] = 'Unauthorized access';
    echo json_encode($response);
    exit;
}

// Check if job ID and status are provided
if (!isset($_POST['job_id']) || !is_numeric($_POST['job_id']) || !isset($_POST['status'])) {
    $response['message'] = 'Invalid job ID or status';
    echo json_encode($response);
    exit;
}

try {
    $job_id = $_POST['job_id'];
    $new_status = $_POST['status'];
    
    // Update the job status
    $update_query = "UPDATE jobs SET status = ? WHERE id = ?";
    $stmt = mysqli_prepare($conn, $update_query);
    if (!$stmt) {
        throw new Exception("Error preparing update statement: " . mysqli_error($conn));
    }
    
    mysqli_stmt_bind_param($stmt, "si", $new_status, $job_id);
    if (!mysqli_stmt_execute($stmt)) {
        throw new Exception("Error updating job status: " . mysqli_stmt_error($stmt));
    }
    
    if (mysqli_affected_rows($conn) > 0) {
        $response['success'] = true;
        $response['message'] = "Job status successfully updated to " . ucfirst($new_status);
        $response['new_status'] = $new_status;
    } else {
        throw new Exception("No changes were made to the job status");
    }
    
} catch (Exception $e) {
    $response['message'] = $e->getMessage();
}

// Return JSON response
header('Content-Type: application/json');
echo json_encode($response); 