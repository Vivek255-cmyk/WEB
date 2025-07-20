<?php
session_start();
require_once '../config.php';

// Check if user is logged in and is an admin
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] != 'admin') {
    header("Location: ../login.php");
    exit();
}

// Initialize variables
$error = '';
$job = null;
$applications = [];

try {
    // Check if job ID is provided
    if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
        throw new Exception("Invalid job ID.");
    }

    $job_id = $_GET['id'];

    // Get job details with company information
    $query = "SELECT j.*, c.name as company_name, c.industry, c.location as company_location, 
                     c.website, c.description as company_description,
                     (SELECT COUNT(*) FROM applications a WHERE a.job_id = j.id) as total_applications,
                     (SELECT COUNT(*) FROM applications a WHERE a.job_id = j.id AND a.status = 'accepted') as accepted_applications,
                     (SELECT COUNT(*) FROM applications a WHERE a.job_id = j.id AND a.status = 'rejected') as rejected_applications
              FROM jobs j
              JOIN company_profiles c ON j.company_id = c.id
              WHERE j.id = ?";

    $stmt = mysqli_prepare($conn, $query);
    if (!$stmt) {
        throw new Exception("Error preparing job statement: " . mysqli_error($conn));
    }

    mysqli_stmt_bind_param($stmt, "i", $job_id);
    if (!mysqli_stmt_execute($stmt)) {
        throw new Exception("Error executing job statement: " . mysqli_stmt_error($stmt));
    }

    $result = mysqli_stmt_get_result($stmt);
    $job = mysqli_fetch_assoc($result);

    if (!$job) {
        throw new Exception("Job not found.");
    }

    // Get applications for this job
    $query = "SELECT a.*, s.full_name, s.course, s.graduation_year, s.skills
              FROM applications a
              JOIN student_profiles s ON a.student_id = s.id
              WHERE a.job_id = ?
              ORDER BY a.apply_date DESC";

    $stmt = mysqli_prepare($conn, $query);
    if (!$stmt) {
        throw new Exception("Error preparing applications statement: " . mysqli_error($conn));
    }

    mysqli_stmt_bind_param($stmt, "i", $job_id);
    if (!mysqli_stmt_execute($stmt)) {
        throw new Exception("Error executing applications statement: " . mysqli_stmt_error($stmt));
    }

    $result = mysqli_stmt_get_result($stmt);
    while ($application = mysqli_fetch_assoc($result)) {
        $applications[] = $application;
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
    <title>View Job - Admin Dashboard</title>
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
        .content-card {
            background: white;
            border-radius: 15px;
            box-shadow: 0 0 20px rgba(0,0,0,0.1);
            margin-bottom: 20px;
        }
        .stats-card {
            transition: transform 0.3s ease;
        }
        .stats-card:hover {
            transform: translateY(-5px);
        }
        .application-card {
            border-left: 4px solid #6c757d;
            transition: transform 0.3s ease;
        }
        .application-card:hover {
            transform: translateY(-3px);
        }
        .application-card.status-pending {
            border-left-color: #ffc107;
        }
        .application-card.status-accepted {
            border-left-color: #198754;
        }
        .application-card.status-rejected {
            border-left-color: #dc3545;
        }
        .status-badge {
            padding: 8px 16px;
            border-radius: 20px;
            font-weight: 500;
        }
        .skill-badge {
            background: linear-gradient(135deg, #6c757d 0%, #495057 100%);
            color: white;
            font-size: 0.8rem;
            padding: 5px 10px;
            border-radius: 15px;
            margin-right: 5px;
            margin-bottom: 5px;
            display: inline-block;
        }
    </style>
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-dark mb-4">
        <div class="container">
            <a class="navbar-brand" href="dashboard.php">Admin Dashboard</a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav me-auto">
                    <li class="nav-item">
                        <a class="nav-link" href="dashboard.php">Dashboard</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="manage_students.php">Students</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="manage_companies.php">Companies</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link active" href="manage_jobs.php">Jobs</a>
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
        <?php if ($error): ?>
            <div class="alert alert-danger" role="alert">
                <?php echo htmlspecialchars($error); ?>
                <br>
                <a href="manage_jobs.php" class="alert-link">Back to Jobs</a>
            </div>
        <?php else: ?>
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h2>Job Details</h2>
                <a href="manage_jobs.php" class="btn btn-secondary">
                    <i class="fas fa-arrow-left me-2"></i>Back to Jobs
                </a>
            </div>

            <div class="row mb-4">
                <div class="col-md-4">
                    <div class="content-card stats-card p-4 bg-primary text-white">
                        <h3 class="h2 mb-0"><?php echo $job['total_applications']; ?></h3>
                        <p class="mb-0">Total Applications</p>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="content-card stats-card p-4 bg-success text-white">
                        <h3 class="h2 mb-0"><?php echo $job['accepted_applications']; ?></h3>
                        <p class="mb-0">Accepted</p>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="content-card stats-card p-4 bg-danger text-white">
                        <h3 class="h2 mb-0"><?php echo $job['rejected_applications']; ?></h3>
                        <p class="mb-0">Rejected</p>
                    </div>
                </div>
            </div>

            <div class="row">
                <div class="col-md-8">
                    <div class="content-card p-4 mb-4">
                        <div class="d-flex justify-content-between align-items-start mb-4">
                            <div>
                                <h3 class="mb-1"><?php echo htmlspecialchars($job['title']); ?></h3>
                                <h5 class="text-muted mb-0"><?php echo htmlspecialchars($job['company_name']); ?></h5>
                            </div>
                            <span class="badge bg-<?php echo $job['status'] == 'active' ? 'success' : 'secondary'; ?>">
                                <?php echo ucfirst($job['status']); ?>
                            </span>
                        </div>

                        <div class="mb-4">
                            <p class="mb-2">
                                <i class="fas fa-building me-2"></i>
                                <strong>Industry:</strong> <?php echo htmlspecialchars($job['industry']); ?>
                            </p>
                            <p class="mb-2">
                                <i class="fas fa-map-marker-alt me-2"></i>
                                <strong>Location:</strong> <?php echo htmlspecialchars($job['location']); ?>
                            </p>
                            <p class="mb-2">
                                <i class="fas fa-briefcase me-2"></i>
                                <strong>Job Type:</strong> <?php echo htmlspecialchars($job['job_type']); ?>
                            </p>
                            <?php if ($job['salary_range']): ?>
                                <p class="mb-2">
                                    <i class="fas fa-money-bill-wave me-2"></i>
                                    <strong>Salary Range:</strong> <?php echo htmlspecialchars($job['salary_range']); ?>
                                </p>
                            <?php endif; ?>
                            <p class="mb-2">
                                <i class="fas fa-graduation-cap me-2"></i>
                                <strong>Required Course:</strong> <?php echo htmlspecialchars($job['required_course']); ?>
                            </p>
                            <p class="mb-2">
                                <i class="fas fa-clock me-2"></i>
                                <strong>Posted:</strong> <?php echo date('F j, Y', strtotime($job['post_date'])); ?>
                            </p>
                            <?php if ($job['deadline_date']): ?>
                                <p class="mb-2">
                                    <i class="fas fa-calendar me-2"></i>
                                    <strong>Deadline:</strong> <?php echo date('F j, Y', strtotime($job['deadline_date'])); ?>
                                </p>
                            <?php endif; ?>
                        </div>

                        <div class="mb-4">
                            <h5>Job Description</h5>
                            <p><?php echo nl2br(htmlspecialchars($job['description'])); ?></p>
                        </div>

                        <?php if ($job['required_skills']): ?>
                            <div>
                                <h5>Required Skills</h5>
                                <div>
                                    <?php foreach (explode(',', $job['required_skills']) as $skill): ?>
                                        <span class="skill-badge">
                                            <i class="fas fa-check-circle me-1"></i><?php echo htmlspecialchars(trim($skill)); ?>
                                        </span>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        <?php endif; ?>
                    </div>

                    <div class="content-card p-4">
                        <h4 class="mb-4">Applications (<?php echo count($applications); ?>)</h4>
                        <?php if (empty($applications)): ?>
                            <div class="alert alert-info">
                                <i class="fas fa-info-circle me-2"></i>No applications received yet.
                            </div>
                        <?php else: ?>
                            <?php foreach ($applications as $application): ?>
                                <div class="application-card p-3 mb-3 status-<?php echo $application['status']; ?>">
                                    <div class="d-flex justify-content-between align-items-start mb-3">
                                        <div>
                                            <h5 class="mb-1"><?php echo htmlspecialchars($application['full_name']); ?></h5>
                                            <p class="text-muted mb-0">
                                                <i class="fas fa-graduation-cap me-2"></i><?php echo htmlspecialchars($application['course']); ?> 
                                                (<?php echo htmlspecialchars($application['graduation_year']); ?>)
                                            </p>
                                        </div>
                                        <span class="badge bg-<?php 
                                            echo $application['status'] == 'pending' ? 'warning' : 
                                                ($application['status'] == 'accepted' ? 'success' : 'danger'); 
                                        ?>">
                                            <?php echo ucfirst($application['status']); ?>
                                        </span>
                                    </div>

                                    <?php if ($application['skills']): ?>
                                        <div class="mb-3">
                                            <?php foreach (explode(',', $application['skills']) as $skill): ?>
                                                <span class="skill-badge">
                                                    <?php echo htmlspecialchars(trim($skill)); ?>
                                                </span>
                                            <?php endforeach; ?>
                                        </div>
                                    <?php endif; ?>

                                    <?php if ($application['cover_letter']): ?>
                                        <div class="mb-3">
                                            <strong>Cover Letter:</strong>
                                            <p class="mb-0"><?php echo nl2br(htmlspecialchars($application['cover_letter'])); ?></p>
                                        </div>
                                    <?php endif; ?>

                                    <div class="d-flex justify-content-between align-items-center">
                                        <small class="text-muted">
                                            Applied: <?php echo date('F j, Y', strtotime($application['apply_date'])); ?>
                                        </small>
                                        <?php if ($application['status'] == 'pending'): ?>
                                            <div class="btn-group">
                                                <a href="update_application.php?id=<?php echo $application['id']; ?>&status=accepted" 
                                                   class="btn btn-success btn-sm">
                                                    <i class="fas fa-check me-1"></i>Accept
                                                </a>
                                                <a href="update_application.php?id=<?php echo $application['id']; ?>&status=rejected" 
                                                   class="btn btn-danger btn-sm">
                                                    <i class="fas fa-times me-1"></i>Reject
                                                </a>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>

                <div class="col-md-4">
                    <div class="content-card p-4">
                        <h4 class="mb-4">Company Information</h4>
                        <p class="mb-3">
                            <strong><i class="fas fa-building me-2"></i>Company:</strong><br>
                            <?php echo htmlspecialchars($job['company_name']); ?>
                        </p>
                        <p class="mb-3">
                            <strong><i class="fas fa-industry me-2"></i>Industry:</strong><br>
                            <?php echo htmlspecialchars($job['industry']); ?>
                        </p>
                        <p class="mb-3">
                            <strong><i class="fas fa-map-marker-alt me-2"></i>Location:</strong><br>
                            <?php echo htmlspecialchars($job['company_location']); ?>
                        </p>
                        <?php if ($job['website']): ?>
                            <p class="mb-3">
                                <strong><i class="fas fa-globe me-2"></i>Website:</strong><br>
                                <a href="<?php echo htmlspecialchars($job['website']); ?>" target="_blank" rel="noopener noreferrer">
                                    <?php echo htmlspecialchars($job['website']); ?>
                                </a>
                            </p>
                        <?php endif; ?>
                        <?php if ($job['company_description']): ?>
                            <p class="mb-0">
                                <strong><i class="fas fa-info-circle me-2"></i>Description:</strong><br>
                                <?php echo nl2br(htmlspecialchars($job['company_description'])); ?>
                            </p>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        <?php endif; ?>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html> 