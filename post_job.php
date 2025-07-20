<?php
session_start();
require_once '../config.php';

// Check if user is logged in and is a company
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] != 'company') {
    header("Location: ../index.php");
    exit();
}

// Initialize variables
$error = '';
$success = '';

try {
    // Get company profile
    $user_id = $_SESSION['user_id'];
    $query = "SELECT * FROM company_profiles WHERE user_id = ?";
    $stmt = mysqli_prepare($conn, $query);
    if (!$stmt) {
        throw new Exception("Error preparing statement: " . mysqli_error($conn));
    }
    
    mysqli_stmt_bind_param($stmt, "i", $user_id);
    if (!mysqli_stmt_execute($stmt)) {
        throw new Exception("Error executing statement: " . mysqli_stmt_error($stmt));
    }
    
    $result = mysqli_stmt_get_result($stmt);
    $company = mysqli_fetch_assoc($result);

    if (!$company) {
        throw new Exception("Company profile not found. Please complete your profile first.");
    }

    // Handle form submission
    if ($_SERVER['REQUEST_METHOD'] == 'POST') {
        $title = trim($_POST['title']);
        $description = trim($_POST['description']);
        $required_skills = trim($_POST['required_skills']);
        $required_course = trim($_POST['required_course']);
        $location = trim($_POST['location']);
        $salary_range = trim($_POST['salary_range']);
        $job_type = trim($_POST['job_type']);
        $experience_level = trim($_POST['experience_level']);
        $deadline_date = trim($_POST['deadline_date']);

        // Validate input
        if (empty($title) || empty($description) || empty($location)) {
            throw new Exception("Please fill in all required fields.");
        }

        // Insert job
        $query = "INSERT INTO jobs (company_id, title, description, required_skills, required_course, 
                                  location, salary_range, job_type, experience_level, deadline_date) 
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
        
        $stmt = mysqli_prepare($conn, $query);
        if (!$stmt) {
            throw new Exception("Error preparing statement: " . mysqli_error($conn));
        }

        mysqli_stmt_bind_param($stmt, "isssssssss", 
            $company['id'], 
            $title, 
            $description, 
            $required_skills, 
            $required_course, 
            $location, 
            $salary_range,
            $job_type,
            $experience_level,
            $deadline_date
        );

        if (mysqli_stmt_execute($stmt)) {
            $success = "Job posted successfully!";
        } else {
            throw new Exception("Error posting job: " . mysqli_stmt_error($stmt));
        }
    }
} catch (Exception $e) {
    $error = $e->getMessage();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Post New Job - Job Portal</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        body {
            background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);
            min-height: 100vh;
        }
        .navbar {
            background: linear-gradient(135deg, #198754 0%, #146c43 100%);
        }
        .post-card {
            background: white;
            border-radius: 15px;
            box-shadow: 0 0 20px rgba(0,0,0,0.1);
            margin-bottom: 20px;
        }
        .btn-primary {
            background: linear-gradient(135deg, #198754 0%, #146c43 100%);
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
            <a class="navbar-brand" href="dashboard.php">Job Portal</a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav me-auto">
                    <li class="nav-item">
                        <a class="nav-link" href="dashboard.php">Dashboard</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link active" href="post_job.php">Post New Job</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="view_applications.php">View Applications</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="profile.php">Company Profile</a>
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
                <div class="post-card p-4">
                    <h3 class="mb-4">Post New Job</h3>

                    <?php if ($error): ?>
                        <div class="alert alert-danger" role="alert">
                            <?php echo htmlspecialchars($error); ?>
                        </div>
                    <?php endif; ?>

                    <?php if ($success): ?>
                        <div class="alert alert-success" role="alert">
                            <?php echo htmlspecialchars($success); ?>
                            <a href="dashboard.php" class="alert-link">Return to Dashboard</a>
                        </div>
                    <?php endif; ?>

                    <form method="POST">
                        <div class="mb-3">
                            <label for="title" class="form-label">Job Title <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="title" name="title" required>
                        </div>

                        <div class="mb-3">
                            <label for="description" class="form-label">Job Description <span class="text-danger">*</span></label>
                            <textarea class="form-control" id="description" name="description" rows="5" required></textarea>
                        </div>

                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="required_skills" class="form-label">Required Skills</label>
                                <input type="text" class="form-control" id="required_skills" name="required_skills">
                                <div class="form-text">Separate skills with commas</div>
                            </div>

                            <div class="col-md-6 mb-3">
                                <label for="required_course" class="form-label">Required Course</label>
                                <input type="text" class="form-control" id="required_course" name="required_course">
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="location" class="form-label">Location <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" id="location" name="location" required>
                            </div>

                            <div class="col-md-6 mb-3">
                                <label for="salary_range" class="form-label">Salary Range</label>
                                <input type="text" class="form-control" id="salary_range" name="salary_range">
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="job_type" class="form-label">Job Type <span class="text-danger">*</span></label>
                                <select class="form-select" id="job_type" name="job_type" required>
                                    <option value="full-time">Full Time</option>
                                    <option value="part-time">Part Time</option>
                                    <option value="internship">Internship</option>
                                    <option value="contract">Contract</option>
                                </select>
                            </div>

                            <div class="col-md-6 mb-3">
                                <label for="experience_level" class="form-label">Experience Level <span class="text-danger">*</span></label>
                                <select class="form-select" id="experience_level" name="experience_level" required>
                                    <option value="entry">Entry Level</option>
                                    <option value="mid">Mid Level</option>
                                    <option value="senior">Senior Level</option>
                                </select>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label for="deadline_date" class="form-label">Application Deadline</label>
                            <input type="date" class="form-control" id="deadline_date" name="deadline_date">
                        </div>

                        <div class="d-grid">
                            <button type="submit" class="btn btn-primary">Post Job</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html> 