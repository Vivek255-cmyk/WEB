<?php
session_start();
require_once '../config.php';

// Check if user is logged in and is an admin
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'admin') {
    header('Location: ../login.php');
    exit();
}

// Initialize variables
$students = [];
$error = '';
$success = '';

// Handle resume upload
if (isset($_POST['upload_resume']) && isset($_FILES['resume_file']) && isset($_POST['student_id'])) {
    $student_id = $conn->real_escape_string($_POST['student_id']);
    $file = $_FILES['resume_file'];
    
    // Validate file
    $allowed_types = ['application/pdf'];
    $max_size = 5 * 1024 * 1024; // 5MB
    
    if (!in_array($file['type'], $allowed_types)) {
        $error = "Only PDF files are allowed.";
    } elseif ($file['size'] > $max_size) {
        $error = "File size must be less than 5MB.";
    } else {
        // Create uploads directory if it doesn't exist
        $upload_dir = '../uploads/resumes/';
        if (!file_exists($upload_dir)) {
            mkdir($upload_dir, 0777, true);
        }
        
        // Generate unique filename
        $file_extension = pathinfo($file['name'], PATHINFO_EXTENSION);
        $filename = 'resume_' . $student_id . '_' . time() . '.' . $file_extension;
        $filepath = $upload_dir . $filename;
        
        if (move_uploaded_file($file['tmp_name'], $filepath)) {
            // Update database
            $relative_path = 'uploads/resumes/' . $filename;
            $sql = "UPDATE student_profiles SET resume = ? WHERE user_id = ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("si", $relative_path, $student_id);
            
            if ($stmt->execute()) {
                $success = "Resume uploaded successfully!";
            } else {
                $error = "Error updating database: " . $stmt->error;
                // Remove uploaded file if database update fails
                unlink($filepath);
            }
            $stmt->close();
        } else {
            $error = "Error uploading file.";
        }
    }
}

// Handle student deletion if requested
if (isset($_POST['delete_student']) && isset($_POST['student_id'])) {
    $student_id = $conn->real_escape_string($_POST['student_id']);
    
    try {
        // Start transaction
        $conn->begin_transaction();
        
        // Delete from student_profiles
        $conn->query("DELETE FROM student_profiles WHERE user_id = $student_id");
        
        // Delete from applications
        $conn->query("DELETE FROM applications WHERE student_id = $student_id");
        
        // Delete from users
        $conn->query("DELETE FROM users WHERE id = $student_id AND user_type = 'student'");
        
        // Commit transaction
        $conn->commit();
        $success = "Student deleted successfully!";
    } catch (Exception $e) {
        $conn->rollback();
        $error = "Error deleting student: " . $e->getMessage();
    }
}

try {
    // First, let's check what columns exist in student_profiles
    $result = $conn->query("DESCRIBE student_profiles");
    $columns = $result->fetch_all(MYSQLI_ASSOC);
    $column_names = array_column($columns, 'Field');
    
    // Build the SELECT part of the query based on available columns
    $select_columns = [
        'u.id',
        'u.username',
        'u.email',
        'u.created_at'
    ];
    
    // Add student profile columns if they exist
    if (in_array('name', $column_names)) {
        $select_columns[] = 'sp.name as student_name';
    }
    if (in_array('course', $column_names)) {
        $select_columns[] = 'sp.course';
    }
    if (in_array('year_level', $column_names)) {
        $select_columns[] = 'sp.year_level';
    }
    if (in_array('contact', $column_names)) {
        $select_columns[] = 'sp.contact';
    }
    if (in_array('resume', $column_names)) {
        $select_columns[] = 'sp.resume';
    }
    
    // Add application count
    $select_columns[] = 'COUNT(a.id) as application_count';
    
    // Build the final query
    $sql = "
        SELECT " . implode(', ', $select_columns) . "
        FROM users u
        LEFT JOIN student_profiles sp ON u.id = sp.user_id
        LEFT JOIN applications a ON u.id = a.student_id
        WHERE u.user_type = 'student'
        GROUP BY u.id
        ORDER BY u.created_at DESC
    ";
    
    $result = $conn->query($sql);
    if ($result === false) {
        throw new Exception("Error executing query: " . $conn->error);
    }
    
    $students = $result->fetch_all(MYSQLI_ASSOC);
} catch (Exception $e) {
    $error = "Error fetching students: " . $e->getMessage();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Students - Job Portal</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.7.2/font/bootstrap-icons.css">
    <style>
        .student-card {
            transition: transform 0.2s;
        }
        .student-card:hover {
            transform: translateY(-5px);
        }
        .action-buttons {
            opacity: 0;
            transition: opacity 0.2s;
        }
        .student-card:hover .action-buttons {
            opacity: 1;
        }
        .resume-upload-form {
            display: none;
        }
        .resume-upload-form.show {
            display: block;
        }
    </style>
</head>
<body>
    <!-- Navigation -->
    <nav class="navbar navbar-expand-lg navbar-dark bg-primary">
        <div class="container">
            <a class="navbar-brand" href="../index.php">Job Portal</a>
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

    <!-- Main Content -->
    <div class="container my-5">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2>Manage Students</h2>
            <div class="text-muted">
                Total Students: <?php echo count($students); ?>
            </div>
        </div>

        <?php if ($success): ?>
            <div class="alert alert-success"><?php echo htmlspecialchars($success); ?></div>
        <?php endif; ?>

        <?php if ($error): ?>
            <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>

        <div class="row">
            <?php if (empty($students)): ?>
                <div class="col-12">
                    <div class="alert alert-info">No students found.</div>
                </div>
            <?php else: ?>
                <?php foreach ($students as $student): ?>
                    <div class="col-md-6 col-lg-4 mb-4">
                        <div class="card student-card h-100">
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-start mb-3">
                                    <h5 class="card-title">
                                        <?php 
                                        if (isset($student['student_name'])) {
                                            echo htmlspecialchars($student['student_name']);
                                        } else {
                                            echo htmlspecialchars($student['username']);
                                        }
                                        ?>
                                    </h5>
                                    <span class="badge bg-success">
                                        Active
                                    </span>
                                </div>
                                
                                <div class="mb-3">
                                    <small class="text-muted">
                                        <i class="bi bi-person"></i> <?php echo htmlspecialchars($student['username']); ?><br>
                                        <i class="bi bi-envelope"></i> <?php echo htmlspecialchars($student['email']); ?><br>
                                        <?php if (isset($student['course'])): ?>
                                            <i class="bi bi-mortarboard"></i> <?php echo htmlspecialchars($student['course']); ?><br>
                                        <?php endif; ?>
                                        <?php if (isset($student['year_level'])): ?>
                                            <i class="bi bi-123"></i> Year <?php echo htmlspecialchars($student['year_level']); ?><br>
                                        <?php endif; ?>
                                        <?php if (isset($student['contact'])): ?>
                                            <i class="bi bi-telephone"></i> <?php echo htmlspecialchars($student['contact']); ?><br>
                                        <?php endif; ?>
                                        <i class="bi bi-calendar"></i> Joined: <?php echo date('M d, Y', strtotime($student['created_at'])); ?><br>
                                        <i class="bi bi-file-earmark-text"></i> Applications: <?php echo $student['application_count']; ?>
                                    </small>
                                </div>

                                <div class="action-buttons">
                                    <?php if (isset($student['resume'])): ?>
                                        <a href="../<?php echo htmlspecialchars($student['resume']); ?>" 
                                           class="btn btn-sm btn-info mb-2 w-100" 
                                           target="_blank">
                                            <i class="bi bi-file-earmark-pdf"></i> View Resume
                                        </a>
                                    <?php endif; ?>
                                    
                                    <button class="btn btn-sm btn-primary mb-2 w-100" 
                                            onclick="toggleResumeUpload(<?php echo $student['id']; ?>)">
                                        <i class="bi bi-upload"></i> Upload Resume
                                    </button>
                                    
                                    <form method="POST" class="d-inline w-100" 
                                          onsubmit="return confirm('Are you sure you want to delete this student? This action cannot be undone.');">
                                        <input type="hidden" name="student_id" value="<?php echo $student['id']; ?>">
                                        <button type="submit" name="delete_student" class="btn btn-sm btn-danger w-100">
                                            <i class="bi bi-trash"></i> Delete Student
                                        </button>
                                    </form>
                                    
                                    <form id="resume-upload-<?php echo $student['id']; ?>" 
                                          class="resume-upload-form mt-2" 
                                          method="POST" 
                                          enctype="multipart/form-data">
                                        <input type="hidden" name="student_id" value="<?php echo $student['id']; ?>">
                                        <div class="mb-2">
                                            <input type="file" 
                                                   name="resume_file" 
                                                   class="form-control form-control-sm" 
                                                   accept=".pdf" 
                                                   required>
                                        </div>
                                        <button type="submit" 
                                                name="upload_resume" 
                                                class="btn btn-sm btn-success w-100">
                                            <i class="bi bi-check2"></i> Submit
                                        </button>
                                    </form>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function toggleResumeUpload(studentId) {
            const form = document.getElementById(`resume-upload-${studentId}`);
            form.classList.toggle('show');
        }
    </script>
</body>
</html> 