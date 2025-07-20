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
$company = null;
$jobs = [];

try {
    // Check if company ID is provided
    if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
        throw new Exception("Invalid company ID.");
    }

    $company_id = $_GET['id'];

    // Get company details with user information
    $query = "SELECT c.*, u.email, u.user_type,
                     (SELECT COUNT(*) FROM jobs j WHERE j.company_id = c.id) as total_jobs,
                     (SELECT COUNT(*) FROM jobs j WHERE j.company_id = c.id AND j.status = 'active') as active_jobs
              FROM company_profiles c
              JOIN users u ON c.user_id = u.id
              WHERE c.id = ?";

    $stmt = mysqli_prepare($conn, $query);
    if (!$stmt) {
        throw new Exception("Error preparing statement: " . mysqli_error($conn));
    }

    mysqli_stmt_bind_param($stmt, "i", $company_id);
    if (!mysqli_stmt_execute($stmt)) {
        throw new Exception("Error executing statement: " . mysqli_stmt_error($stmt));
    }

    $result = mysqli_stmt_get_result($stmt);
    $company = mysqli_fetch_assoc($result);

    if (!$company) {
        throw new Exception("Company not found.");
    }

    // Get company's jobs
    $query = "SELECT * FROM jobs WHERE company_id = ? ORDER BY post_date DESC";
    $stmt = mysqli_prepare($conn, $query);
    if (!$stmt) {
        throw new Exception("Error preparing jobs statement: " . mysqli_error($conn));
    }

    mysqli_stmt_bind_param($stmt, "i", $company_id);
    if (!mysqli_stmt_execute($stmt)) {
        throw new Exception("Error executing jobs statement: " . mysqli_stmt_error($stmt));
    }

    $result = mysqli_stmt_get_result($stmt);
    while ($job = mysqli_fetch_assoc($result)) {
        $jobs[] = $job;
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
    <title>Company Details - Admin Dashboard</title>
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
            background: linear-gradient(135deg, #0d6efd 0%, #0a58ca 100%);
            color: white;
            border-radius: 15px;
            padding: 20px;
            text-align: center;
        }
        .job-card {
            background: white;
            border-radius: 10px;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
            padding: 15px;
            margin-bottom: 15px;
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
                        <a class="nav-link active" href="manage_companies.php">Companies</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="manage_jobs.php">Jobs</a>
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
                <a href="manage_companies.php" class="alert-link">Back to Companies</a>
            </div>
        <?php else: ?>
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h2>Company Details</h2>
                <a href="manage_companies.php" class="btn btn-secondary">
                    <i class="fas fa-arrow-left me-2"></i>Back to Companies
                </a>
            </div>

            <div class="row mb-4">
                <div class="col-md-4">
                    <div class="stats-card">
                        <h3 class="h2 mb-0"><?php echo $company['total_jobs']; ?></h3>
                        <p class="mb-0">Total Jobs</p>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="stats-card">
                        <h3 class="h2 mb-0"><?php echo $company['active_jobs']; ?></h3>
                        <p class="mb-0">Active Jobs</p>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="stats-card">
                        <h3 class="h2 mb-0"><?php echo $company['user_type'] == 'company' ? 'Active' : 'Inactive'; ?></h3>
                        <p class="mb-0">Status</p>
                    </div>
                </div>
            </div>

            <div class="row">
                <div class="col-md-8">
                    <div class="content-card p-4">
                        <div class="d-flex justify-content-between align-items-start mb-4">
                            <div>
                                <h3 class="mb-1"><?php echo htmlspecialchars($company['name']); ?></h3>
                                <h5 class="text-muted mb-0"><?php echo htmlspecialchars($company['industry']); ?></h5>
                            </div>
                        </div>

                        <div class="mb-4">
                            <p class="mb-2">
                                <i class="fas fa-map-marker-alt me-2"></i>
                                <strong>Location:</strong> <?php echo htmlspecialchars($company['location']); ?>
                            </p>
                            <p class="mb-2">
                                <i class="fas fa-envelope me-2"></i>
                                <strong>Email:</strong> <?php echo htmlspecialchars($company['email']); ?>
                            </p>
                            <?php if ($company['website']): ?>
                                <p class="mb-2">
                                    <i class="fas fa-globe me-2"></i>
                                    <strong>Website:</strong> 
                                    <a href="<?php echo htmlspecialchars($company['website']); ?>" target="_blank" rel="noopener noreferrer">
                                        <?php echo htmlspecialchars($company['website']); ?>
                                    </a>
                                </p>
                            <?php endif; ?>
                        </div>

                        <?php if ($company['description']): ?>
                            <div class="mb-4">
                                <h5>About the Company</h5>
                                <p class="text-muted mb-0">
                                    <?php echo nl2br(htmlspecialchars($company['description'])); ?>
                                </p>
                            </div>
                        <?php endif; ?>
                    </div>

                    <div class="content-card p-4">
                        <h4 class="mb-4">Posted Jobs</h4>
                        <?php if (empty($jobs)): ?>
                            <div class="alert alert-info">
                                <i class="fas fa-info-circle me-2"></i>No jobs posted yet.
                            </div>
                        <?php else: ?>
                            <?php foreach ($jobs as $job): ?>
                                <div class="job-card">
                                    <div class="d-flex justify-content-between align-items-start mb-2">
                                        <div>
                                            <h5 class="mb-1"><?php echo htmlspecialchars($job['title']); ?></h5>
                                            <p class="text-muted mb-0">
                                                <i class="fas fa-briefcase me-2"></i><?php echo htmlspecialchars($job['job_type']); ?>
                                            </p>
                                        </div>
                                        <span class="badge bg-<?php echo $job['status'] == 'active' ? 'success' : 'secondary'; ?>">
                                            <?php echo ucfirst($job['status']); ?>
                                        </span>
                                    </div>
                                    <p class="mb-2">
                                        <i class="fas fa-map-marker-alt me-2"></i><?php echo htmlspecialchars($job['location']); ?>
                                    </p>
                                    <p class="mb-0">
                                        <i class="fas fa-clock me-2"></i>Posted: <?php echo date('F j, Y', strtotime($job['post_date'])); ?>
                                    </p>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        <?php endif; ?>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html> 