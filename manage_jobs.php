<?php
session_start();
require_once '../config.php';

// Check if user is logged in and is an admin
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] != 'admin') {
    header("Location: ../login.php");
    exit();
}

// Initialize variables
$jobs = [];
$error = '';
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$company = isset($_GET['company']) ? trim($_GET['company']) : '';
$type = isset($_GET['type']) ? trim($_GET['type']) : '';
$status = isset($_GET['status']) ? trim($_GET['status']) : '';

try {
    // Base query
    $query = "SELECT j.*, c.name as company_name, 
                     (SELECT COUNT(*) FROM applications a WHERE a.job_id = j.id) as application_count
              FROM jobs j
              JOIN company_profiles c ON j.company_id = c.id
              WHERE 1=1";
    $params = [];
    $types = "";

    // Add search conditions
    if ($search) {
        $query .= " AND (j.title LIKE ? OR j.description LIKE ? OR j.required_skills LIKE ?)";
        $searchParam = "%$search%";
        $params = array_merge($params, [$searchParam, $searchParam, $searchParam]);
        $types .= "sss";
    }

    if ($company) {
        $query .= " AND c.name LIKE ?";
        $params[] = "%$company%";
        $types .= "s";
    }

    if ($type && $type != 'All Types') {
        $query .= " AND j.job_type = ?";
        $params[] = $type;
        $types .= "s";
    }

    if ($status && $status != 'All Status') {
        $query .= " AND j.status = ?";
        $params[] = strtolower($status);
        $types .= "s";
    }

    $query .= " ORDER BY j.post_date DESC";

    $stmt = mysqli_prepare($conn, $query);
    if (!$stmt) {
        throw new Exception("Error preparing statement: " . mysqli_error($conn));
    }

    if (!empty($params)) {
        mysqli_stmt_bind_param($stmt, $types, ...$params);
    }

    if (!mysqli_stmt_execute($stmt)) {
        throw new Exception("Error executing statement: " . mysqli_stmt_error($stmt));
    }

    $result = mysqli_stmt_get_result($stmt);
    while ($row = mysqli_fetch_assoc($result)) {
        $jobs[] = $row;
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
    <title>Manage Jobs - Admin Dashboard</title>
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
        .search-card {
            background: white;
            border-radius: 15px;
            box-shadow: 0 0 20px rgba(0,0,0,0.1);
            padding: 2rem;
            margin-bottom: 2rem;
        }
        .job-card {
            background: white;
            border-radius: 15px;
            box-shadow: 0 0 15px rgba(0,0,0,0.1);
            padding: 1.5rem;
            margin-bottom: 1rem;
            transition: transform 0.2s;
        }
        .job-card:hover {
            transform: translateY(-3px);
        }
        .skill-badge {
            background: #e9ecef;
            color: #495057;
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
        <div class="search-card">
            <h2 class="mb-4">Search Jobs</h2>
            <form method="GET" class="row g-3">
                <div class="col-md-3">
                    <input type="text" class="form-control" name="search" placeholder="Search by title, description or skills" 
                           value="<?php echo htmlspecialchars($search); ?>">
                </div>
                <div class="col-md-3">
                    <input type="text" class="form-control" name="company" placeholder="Company" 
                           value="<?php echo htmlspecialchars($company); ?>">
                </div>
                <div class="col-md-2">
                    <select class="form-select" name="type">
                        <option value="All Types" <?php echo $type == 'All Types' ? 'selected' : ''; ?>>All Types</option>
                        <option value="Full Time" <?php echo $type == 'Full Time' ? 'selected' : ''; ?>>Full Time</option>
                        <option value="Part Time" <?php echo $type == 'Part Time' ? 'selected' : ''; ?>>Part Time</option>
                        <option value="Internship" <?php echo $type == 'Internship' ? 'selected' : ''; ?>>Internship</option>
                        <option value="Contract" <?php echo $type == 'Contract' ? 'selected' : ''; ?>>Contract</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <select class="form-select" name="status">
                        <option value="All Status" <?php echo $status == 'All Status' ? 'selected' : ''; ?>>All Status</option>
                        <option value="Active" <?php echo $status == 'Active' ? 'selected' : ''; ?>>Active</option>
                        <option value="Closed" <?php echo $status == 'Closed' ? 'selected' : ''; ?>>Closed</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <button type="submit" class="btn btn-primary w-100">Search</button>
                </div>
            </form>
        </div>

        <?php if ($error): ?>
            <div class="alert alert-danger" role="alert">
                <?php echo htmlspecialchars($error); ?>
            </div>
        <?php else: ?>
            <?php if (empty($jobs)): ?>
                <div class="alert alert-info" role="alert">
                    <i class="fas fa-info-circle me-2"></i>No jobs found matching your criteria.
                </div>
            <?php else: ?>
                <?php foreach ($jobs as $job): ?>
                    <div class="job-card">
                        <div class="d-flex justify-content-between align-items-start mb-3">
                            <div>
                                <h3 class="mb-1"><?php echo htmlspecialchars($job['title']); ?></h3>
                                <h5 class="text-muted mb-0"><?php echo htmlspecialchars($job['company_name']); ?></h5>
                            </div>
                            <span class="badge bg-<?php echo $job['status'] == 'active' ? 'success' : 'secondary'; ?>">
                                <?php echo ucfirst($job['status']); ?>
                            </span>
                        </div>

                        <div class="mb-3">
                            <p class="mb-2">
                                <i class="fas fa-map-marker-alt me-2"></i><?php echo htmlspecialchars($job['location']); ?>
                                <span class="mx-3">|</span>
                                <i class="fas fa-briefcase me-2"></i><?php echo htmlspecialchars($job['job_type']); ?>
                                <?php if ($job['salary_range']): ?>
                                    <span class="mx-3">|</span>
                                    <i class="fas fa-money-bill-wave me-2"></i><?php echo htmlspecialchars($job['salary_range']); ?>
                                <?php endif; ?>
                            </p>
                            <p class="mb-2">
                                <i class="fas fa-graduation-cap me-2"></i>Required Course: <?php echo htmlspecialchars($job['required_course']); ?>
                            </p>
                            <p class="mb-2">
                                <i class="fas fa-clock me-2"></i>Posted: <?php echo date('F j, Y', strtotime($job['post_date'])); ?>
                                <?php if ($job['deadline_date']): ?>
                                    <span class="mx-3">|</span>
                                    <i class="fas fa-calendar me-2"></i>Deadline: <?php echo date('F j, Y', strtotime($job['deadline_date'])); ?>
                                <?php endif; ?>
                            </p>
                        </div>

                        <?php if ($job['required_skills']): ?>
                            <div class="mb-3">
                                <?php foreach (explode(',', $job['required_skills']) as $skill): ?>
                                    <span class="skill-badge">
                                        <i class="fas fa-check-circle me-1"></i><?php echo htmlspecialchars(trim($skill)); ?>
                                    </span>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>

                        <div class="d-flex justify-content-between align-items-center">
                            <span class="text-muted">
                                <i class="fas fa-users me-2"></i><?php echo $job['application_count']; ?> Applications
                            </span>
                            <div class="btn-group">
                                <a href="view_job.php?id=<?php echo $job['id']; ?>" class="btn btn-primary btn-sm">
                                    <i class="fas fa-eye me-1"></i>View
                                </a>
                                <button type="button" class="btn btn-warning btn-sm" 
                                        onclick="toggleJobStatus(<?php echo $job['id']; ?>, '<?php echo $job['status']; ?>')">
                                    <i class="fas fa-times me-1"></i>Close
                                </button>
                                <button type="button" class="btn btn-danger btn-sm" 
                                        onclick="deleteJob(<?php echo $job['id']; ?>)">
                                    <i class="fas fa-trash me-1"></i>Delete
                                </button>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        <?php endif; ?>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function toggleJobStatus(jobId, currentStatus) {
            const newStatus = currentStatus === 'active' ? 'closed' : 'active';
            fetch('toggle_job_status.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: `job_id=${jobId}&status=${newStatus}`
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    location.reload();
                } else {
                    console.error('Error:', data.message);
                }
            })
            .catch(error => {
                console.error('Error:', error);
            });
        }

        function deleteJob(jobId) {
            if (confirm('Are you sure you want to delete this job? This action cannot be undone.')) {
                fetch('delete_job.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: `job_id=${jobId}`
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        location.reload();
                    } else {
                        alert('Error: ' + data.message);
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('An error occurred while deleting the job.');
                });
            }
        }
    </script>
</body>
</html> 