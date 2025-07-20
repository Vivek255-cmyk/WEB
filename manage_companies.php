<?php
session_start();
require_once '../config.php';

// Check if user is logged in and is an admin
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] != 'admin') {
    header("Location: ../login.php");
    exit();
}

// Initialize variables
$companies = [];
$error = '';
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$industry = isset($_GET['industry']) ? trim($_GET['industry']) : '';

try {
    // Base query
    $query = "SELECT c.*, 
                     (SELECT COUNT(*) FROM jobs j WHERE j.company_id = c.id) as total_jobs,
                     (SELECT COUNT(*) FROM jobs j WHERE j.company_id = c.id AND j.status = 'active') as active_jobs,
                     u.email
              FROM company_profiles c
              JOIN users u ON c.user_id = u.id
              WHERE 1=1";
    
    $params = [];
    $types = "";

    // Add search conditions
    if ($search) {
        $query .= " AND (c.name LIKE ? OR c.industry LIKE ? OR c.location LIKE ?)";
        $searchParam = "%$search%";
        $params = array_merge($params, [$searchParam, $searchParam, $searchParam]);
        $types .= "sss";
    }

    if ($industry) {
        $query .= " AND c.industry = ?";
        $params[] = $industry;
        $types .= "s";
    }

    $query .= " ORDER BY c.name ASC";

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
        $companies[] = $row;
    }

    // Get unique industries for filter
    $industries = [];
    $query = "SELECT DISTINCT industry FROM company_profiles WHERE industry IS NOT NULL AND industry != '' ORDER BY industry";
    $result = mysqli_query($conn, $query);
    if ($result) {
        while ($row = mysqli_fetch_assoc($result)) {
            $industries[] = $row['industry'];
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
    <title>Manage Companies - Admin Dashboard</title>
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
        .company-card {
            background: white;
            border-radius: 15px;
            box-shadow: 0 0 15px rgba(0,0,0,0.1);
            padding: 1.5rem;
            margin-bottom: 1rem;
            transition: transform 0.2s;
        }
        .company-card:hover {
            transform: translateY(-3px);
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
        <div class="search-card">
            <h2 class="mb-4">Search Companies</h2>
            <form method="GET" class="row g-3">
                <div class="col-md-5">
                    <input type="text" class="form-control" name="search" 
                           placeholder="Search by company name, industry or location" 
                           value="<?php echo htmlspecialchars($search); ?>">
                </div>
                <div class="col-md-5">
                    <select class="form-select" name="industry">
                        <option value="">All Industries</option>
                        <?php foreach ($industries as $ind): ?>
                            <option value="<?php echo htmlspecialchars($ind); ?>" 
                                    <?php echo $industry === $ind ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($ind); ?>
                            </option>
                        <?php endforeach; ?>
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
            <?php if (empty($companies)): ?>
                <div class="alert alert-info" role="alert">
                    <i class="fas fa-info-circle me-2"></i>No companies found matching your criteria.
                </div>
            <?php else: ?>
                <?php foreach ($companies as $company): ?>
                    <div class="company-card">
                        <div class="d-flex justify-content-between align-items-start mb-3">
                            <div>
                                <h3 class="mb-1"><?php echo htmlspecialchars($company['name']); ?></h3>
                                <h5 class="text-muted mb-0"><?php echo htmlspecialchars($company['industry']); ?></h5>
                            </div>
                            <div class="text-end">
                                <p class="mb-1">
                                    <span class="badge bg-primary">
                                        <i class="fas fa-briefcase me-1"></i><?php echo $company['total_jobs']; ?> Total Jobs
                                    </span>
                                </p>
                                <p class="mb-0">
                                    <span class="badge bg-success">
                                        <i class="fas fa-check-circle me-1"></i><?php echo $company['active_jobs']; ?> Active Jobs
                                    </span>
                                </p>
                            </div>
                        </div>

                        <div class="mb-3">
                            <p class="mb-2">
                                <i class="fas fa-map-marker-alt me-2"></i><?php echo htmlspecialchars($company['location']); ?>
                            </p>
                            <p class="mb-2">
                                <i class="fas fa-envelope me-2"></i><?php echo htmlspecialchars($company['email']); ?>
                            </p>
                            <?php if ($company['website']): ?>
                                <p class="mb-2">
                                    <i class="fas fa-globe me-2"></i>
                                    <a href="<?php echo htmlspecialchars($company['website']); ?>" 
                                       target="_blank" rel="noopener noreferrer">
                                        <?php echo htmlspecialchars($company['website']); ?>
                                    </a>
                                </p>
                            <?php endif; ?>
                        </div>

                        <?php if ($company['description']): ?>
                            <div class="mb-3">
                                <p class="text-muted mb-0">
                                    <?php echo nl2br(htmlspecialchars($company['description'])); ?>
                                </p>
                            </div>
                        <?php endif; ?>

                        <div class="d-flex justify-content-end">
                            <div class="btn-group">
                                <a href="view_company.php?id=<?php echo $company['id']; ?>" class="btn btn-primary btn-sm">
                                    <i class="fas fa-eye me-1"></i>View Details
                                </a>
                                <button type="button" class="btn btn-danger btn-sm" 
                                        onclick="deleteCompany(<?php echo $company['id']; ?>)">
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
        function deleteCompany(companyId) {
            if (confirm('Are you sure you want to delete this company? This will also delete all their jobs and applications. This action cannot be undone.')) {
                fetch('delete_company.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: `company_id=${companyId}`
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
                    alert('An error occurred while deleting the company.');
                });
            }
        }
    </script>
</body>
</html> 