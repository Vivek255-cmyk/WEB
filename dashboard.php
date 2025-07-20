<?php
session_start();
require_once '../config.php';

// Check if user is logged in and is an admin
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] != 'admin') {
    header("Location: ../index.php");
    exit();
}

// Initialize variables
$total_students = 0;
$total_companies = 0;
$total_jobs = 0;
$total_applications = 0;
$recent_applications = [];
$recent_jobs = [];

try {
    // Get total students count
    $students_query = "SELECT COUNT(*) as count FROM student_profiles";
    $students_result = mysqli_query($conn, $students_query);
    if ($students_result) {
        $row = mysqli_fetch_assoc($students_result);
        $total_students = $row['count'];
    }

    // Get total companies count
    $companies_query = "SELECT COUNT(*) as count FROM company_profiles";
    $companies_result = mysqli_query($conn, $companies_query);
    if ($companies_result) {
        $row = mysqli_fetch_assoc($companies_result);
        $total_companies = $row['count'];
    }

    // Get total jobs count
    $jobs_query = "SELECT COUNT(*) as count FROM jobs";
    $jobs_result = mysqli_query($conn, $jobs_query);
    if ($jobs_result) {
        $row = mysqli_fetch_assoc($jobs_result);
        $total_jobs = $row['count'];
    }

    // Get total applications count
    $applications_query = "SELECT COUNT(*) as count FROM applications";
    $applications_result = mysqli_query($conn, $applications_query);
    if ($applications_result) {
        $row = mysqli_fetch_assoc($applications_result);
        $total_applications = $row['count'];
    }

    // Get recent applications
    $recent_apps_query = "SELECT a.*, j.title as job_title, s.full_name as student_name, c.name as company_name 
                         FROM applications a 
                         JOIN jobs j ON a.job_id = j.id 
                         JOIN student_profiles s ON a.student_id = s.id 
                         JOIN company_profiles c ON j.company_id = c.id 
                         ORDER BY a.apply_date DESC LIMIT 5";
    $recent_apps_result = mysqli_query($conn, $recent_apps_query);
    if ($recent_apps_result) {
        while ($row = mysqli_fetch_assoc($recent_apps_result)) {
            $recent_applications[] = $row;
        }
    }

    // Get recent jobs
    $recent_jobs_query = "SELECT j.*, c.name as company_name 
                         FROM jobs j 
                         JOIN company_profiles c ON j.company_id = c.id 
                         ORDER BY j.post_date DESC LIMIT 5";
    $recent_jobs_result = mysqli_query($conn, $recent_jobs_query);
    if ($recent_jobs_result) {
        while ($row = mysqli_fetch_assoc($recent_jobs_result)) {
            $recent_jobs[] = $row;
        }
    }

} catch (Exception $e) {
    // Log error but don't show to user
    error_log("Dashboard error: " . $e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - Job Portal</title>
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
        .stats-card {
            background: white;
            border-radius: 15px;
            box-shadow: 0 0 20px rgba(0,0,0,0.1);
            transition: transform 0.3s ease;
        }
        .stats-card:hover {
            transform: translateY(-5px);
        }
        .stats-icon {
            font-size: 2.5rem;
            color: #0d6efd;
        }
        .stats-number {
            font-size: 2rem;
            font-weight: bold;
            color: #0d6efd;
        }
        .stats-label {
            color: #6c757d;
        }
        .recent-card {
            background: white;
            border-radius: 15px;
            box-shadow: 0 0 20px rgba(0,0,0,0.1);
            margin-bottom: 20px;
        }
        .badge-status {
            padding: 8px 12px;
            border-radius: 20px;
            font-weight: 500;
        }
        .badge-pending {
            background: #ffc107;
            color: #000;
        }
        .badge-accepted {
            background: #198754;
            color: white;
        }
        .badge-rejected {
            background: #dc3545;
            color: white;
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
                        <a class="nav-link active" href="dashboard.php">Dashboard</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="manage_students.php">Manage Students</a>
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
        <div class="row mb-4">
            <div class="col-md-3">
                <div class="stats-card p-4 text-center">
                    <i class="fas fa-user-graduate stats-icon mb-3"></i>
                    <div class="stats-number"><?php echo $total_students; ?></div>
                    <div class="stats-label">Total Students</div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stats-card p-4 text-center">
                    <i class="fas fa-building stats-icon mb-3"></i>
                    <div class="stats-number"><?php echo $total_companies; ?></div>
                    <div class="stats-label">Total Companies</div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stats-card p-4 text-center">
                    <i class="fas fa-briefcase stats-icon mb-3"></i>
                    <div class="stats-number"><?php echo $total_jobs; ?></div>
                    <div class="stats-label">Total Jobs</div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stats-card p-4 text-center">
                    <i class="fas fa-file-alt stats-icon mb-3"></i>
                    <div class="stats-number"><?php echo $total_applications; ?></div>
                    <div class="stats-label">Total Applications</div>
                </div>
            </div>
        </div>

        <div class="row">
            <div class="col-md-6">
                <div class="recent-card p-4">
                    <h4 class="mb-4">Recent Applications</h4>
                    <?php if (!empty($recent_applications)): ?>
                        <?php foreach ($recent_applications as $application): ?>
                            <div class="mb-3 p-3 border rounded">
                                <div class="d-flex justify-content-between align-items-center mb-2">
                                    <h6 class="mb-0"><?php echo htmlspecialchars($application['job_title']); ?></h6>
                                    <span class="badge badge-status badge-<?php echo strtolower($application['status']); ?>">
                                        <?php echo ucfirst($application['status']); ?>
                                    </span>
                                </div>
                                <div class="text-muted small">
                                    <div>Student: <?php echo htmlspecialchars($application['student_name']); ?></div>
                                    <div>Company: <?php echo htmlspecialchars($application['company_name']); ?></div>
                                    <div>Applied: <?php echo date('M d, Y', strtotime($application['apply_date'])); ?></div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <p class="text-muted">No recent applications found.</p>
                    <?php endif; ?>
                </div>
            </div>
            <div class="col-md-6">
                <div class="recent-card p-4">
                    <h4 class="mb-4">Recent Jobs</h4>
                    <?php if (!empty($recent_jobs)): ?>
                        <?php foreach ($recent_jobs as $job): ?>
                            <div class="mb-3 p-3 border rounded">
                                <h6 class="mb-2"><?php echo htmlspecialchars($job['title']); ?></h6>
                                <div class="text-muted small">
                                    <div>Company: <?php echo htmlspecialchars($job['company_name']); ?></div>
                                    <div>Posted: <?php echo date('M d, Y', strtotime($job['post_date'])); ?></div>
                                    <div>Type: <?php echo ucfirst($job['job_type']); ?></div>
                                    <div>Experience: <?php echo ucfirst($job['experience_level']); ?></div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <p class="text-muted">No recent jobs found.</p>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html> 