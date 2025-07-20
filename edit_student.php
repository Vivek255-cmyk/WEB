<?php
session_start();
require_once '../config.php';

// Check if user is logged in and is an admin
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] != 'admin') {
    header("Location: ../index.php");
    exit();
}

// Check if student ID is provided
if (!isset($_GET['id'])) {
    header("Location: manage_students.php");
    exit();
}

$student_id = (int)$_GET['id'];
$error = '';
$success = '';

// Get student data
try {
    $query = "SELECT s.*, u.email, u.username 
              FROM student_profiles s 
              JOIN users u ON s.user_id = u.id 
              WHERE s.id = ?";
    
    $stmt = mysqli_prepare($conn, $query);
    if (!$stmt) {
        throw new Exception("Error preparing statement: " . mysqli_error($conn));
    }
    
    mysqli_stmt_bind_param($stmt, "i", $student_id);
    if (!mysqli_stmt_execute($stmt)) {
        throw new Exception("Error executing statement: " . mysqli_stmt_error($stmt));
    }
    
    $result = mysqli_stmt_get_result($stmt);
    $student = mysqli_fetch_assoc($result);
    
    if (!$student) {
        header("Location: manage_students.php");
        exit();
    }

    // Handle form submission
    if ($_SERVER['REQUEST_METHOD'] == 'POST') {
        $full_name = trim($_POST['full_name']);
        $course = trim($_POST['course']);
        $graduation_year = trim($_POST['graduation_year']);
        $skills = trim($_POST['skills']);
        $email = trim($_POST['email']);

        // Validate input
        if (empty($full_name) || empty($course) || empty($graduation_year) || empty($email)) {
            throw new Exception("Please fill in all required fields.");
        }

        // Start transaction
        mysqli_begin_transaction($conn);

        try {
            // Update student profile
            $update_profile = "UPDATE student_profiles 
                             SET full_name = ?, course = ?, graduation_year = ?, skills = ? 
                             WHERE id = ?";
            $stmt = mysqli_prepare($conn, $update_profile);
            if (!$stmt) {
                throw new Exception("Error preparing profile update: " . mysqli_error($conn));
            }
            
            mysqli_stmt_bind_param($stmt, "ssisi", $full_name, $course, $graduation_year, $skills, $student_id);
            if (!mysqli_stmt_execute($stmt)) {
                throw new Exception("Error updating profile: " . mysqli_stmt_error($stmt));
            }

            // Update user email
            $update_user = "UPDATE users SET email = ? WHERE id = ?";
            $stmt = mysqli_prepare($conn, $update_user);
            if (!$stmt) {
                throw new Exception("Error preparing user update: " . mysqli_error($conn));
            }
            
            mysqli_stmt_bind_param($stmt, "si", $email, $student['user_id']);
            if (!mysqli_stmt_execute($stmt)) {
                throw new Exception("Error updating user: " . mysqli_stmt_error($stmt));
            }

            mysqli_commit($conn);
            $success = "Student profile updated successfully!";

            // Refresh student data
            $stmt = mysqli_prepare($conn, $query);
            mysqli_stmt_bind_param($stmt, "i", $student_id);
            mysqli_stmt_execute($stmt);
            $result = mysqli_stmt_get_result($stmt);
            $student = mysqli_fetch_assoc($result);

        } catch (Exception $e) {
            mysqli_rollback($conn);
            throw new Exception("Error updating student: " . $e->getMessage());
        }
    }

} catch (Exception $e) {
    $error = $e->getMessage();
    error_log("Error in edit_student.php: " . $error);
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Student - Admin Dashboard</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        body {
            background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);
            min-height: 100vh;
        }
        .navbar {
            background: linear-gradient(135deg, #0d6efd 0%, #0a58ca 100%);
        }
        .edit-card {
            background: white;
            border-radius: 15px;
            box-shadow: 0 0 20px rgba(0,0,0,0.1);
            margin-bottom: 20px;
        }
        .btn-primary {
            background: linear-gradient(135deg, #0d6efd 0%, #0a58ca 100%);
            border: none;
            padding: 10px 20px;
            border-radius: 10px;
            transition: transform 0.3s ease;
        }
        .btn-primary:hover {
            transform: translateY(-3px);
        }
    </style>
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-dark mb-4">
        <div class="container">
            <a class="navbar-brand" href="dashboard.php">Job Portal Admin</a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav me-auto">
                    <li class="nav-item">
                        <a class="nav-link" href="dashboard.php">Dashboard</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link active" href="manage_students.php">Manage Students</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="manage_companies.php">Manage Companies</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="manage_jobs.php">Manage Jobs</a>
                    </li>
                </ul>
                <ul class="navbar-nav">
                    <li class="nav-item">
                        <a class="nav-link" href="../logout.php">Logout</a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <div class="container">
        <div class="row justify-content-center">
            <div class="col-md-8">
                <div class="edit-card p-4">
                    <h3 class="mb-4">Edit Student Profile</h3>
                    
                    <?php if ($error): ?>
                        <div class="alert alert-danger" role="alert">
                            <?php echo htmlspecialchars($error); ?>
                        </div>
                    <?php endif; ?>

                    <?php if ($success): ?>
                        <div class="alert alert-success" role="alert">
                            <?php echo htmlspecialchars($success); ?>
                        </div>
                    <?php endif; ?>

                    <form method="POST">
                        <div class="mb-3">
                            <label for="username" class="form-label">Username</label>
                            <input type="text" class="form-control" id="username" value="<?php echo htmlspecialchars($student['username']); ?>" disabled>
                            <div class="form-text">Username cannot be changed</div>
                        </div>

                        <div class="mb-3">
                            <label for="email" class="form-label">Email <span class="text-danger">*</span></label>
                            <input type="email" class="form-control" id="email" name="email" value="<?php echo htmlspecialchars($student['email']); ?>" required>
                        </div>

                        <div class="mb-3">
                            <label for="full_name" class="form-label">Full Name <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="full_name" name="full_name" value="<?php echo htmlspecialchars($student['full_name']); ?>" required>
                        </div>

                        <div class="mb-3">
                            <label for="course" class="form-label">Course <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="course" name="course" value="<?php echo htmlspecialchars($student['course']); ?>" required>
                        </div>

                        <div class="mb-3">
                            <label for="graduation_year" class="form-label">Graduation Year <span class="text-danger">*</span></label>
                            <input type="number" class="form-control" id="graduation_year" name="graduation_year" value="<?php echo htmlspecialchars($student['graduation_year']); ?>" required>
                        </div>

                        <div class="mb-3">
                            <label for="skills" class="form-label">Skills (comma-separated)</label>
                            <input type="text" class="form-control" id="skills" name="skills" value="<?php echo htmlspecialchars($student['skills']); ?>">
                            <div class="form-text">Example: PHP, MySQL, JavaScript, HTML, CSS</div>
                        </div>

                        <div class="d-flex justify-content-between">
                            <a href="manage_students.php" class="btn btn-secondary">Back to List</a>
                            <button type="submit" class="btn btn-primary">Update Profile</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html> 