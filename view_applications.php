<?php
session_start();
require_once '../config.php';

// Check if user is logged in and is a company
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] != 'company') {
    header("Location: ../index.php");
    exit();
}

// Get company profile ID
$company_query = "SELECT id FROM company_profiles WHERE user_id = ?";
$stmt = mysqli_prepare($conn, $company_query);
mysqli_stmt_bind_param($stmt, "i", $_SESSION['user_id']);
mysqli_stmt_execute($stmt);
$company_result = mysqli_stmt_get_result($stmt);
$company = mysqli_fetch_assoc($company_result);
$company_id = $company['id'];

// Fetch applications for company's jobs
$query = "SELECT a.*, j.title as job_title, s.full_name, s.course, s.graduation_year, s.skills
          FROM applications a
          JOIN jobs j ON a.job_id = j.id
          JOIN student_profiles s ON a.student_id = s.id
          WHERE j.company_id = ?
          ORDER BY a.apply_date DESC";
$stmt = mysqli_prepare($conn, $query);
mysqli_stmt_bind_param($stmt, "i", $company_id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>View Applications - Job Portal</title>
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
        .card {
            border: none;
            border-radius: 15px;
            box-shadow: 0 0 20px rgba(0,0,0,0.1);
            margin-bottom: 20px;
        }
        .badge-pending {
            background: linear-gradient(135deg, #ffc107 0%, #ffb300 100%);
        }
        .badge-accepted {
            background: linear-gradient(135deg, #198754 0%, #146c43 100%);
        }
        .badge-rejected {
            background: linear-gradient(135deg, #dc3545 0%, #c82333 100%);
        }
        .application-header {
            background: linear-gradient(135deg, #198754 0%, #146c43 100%);
            color: white;
            border-radius: 15px 15px 0 0;
            padding: 15px;
        }
        .btn-action {
            border-radius: 8px;
            padding: 8px 15px;
            font-weight: 600;
        }
        .btn-accept {
            background: linear-gradient(135deg, #198754 0%, #146c43 100%);
            border: none;
        }
        .btn-reject {
            background: linear-gradient(135deg, #dc3545 0%, #c82333 100%);
            border: none;
        }
        .btn-accept:hover, .btn-reject:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
        }
    </style>
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-dark">
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
                        <a class="nav-link" href="post_job.php">Post Job</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link active" href="view_applications.php">Applications</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="profile.php">Profile</a>
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

    <div class="container mt-4">
        <h2 class="mb-4">Job Applications</h2>
        
        <?php if (mysqli_num_rows($result) > 0): ?>
            <?php while ($application = mysqli_fetch_assoc($result)): ?>
                <div class="card">
                    <div class="application-header">
                        <div class="d-flex justify-content-between align-items-center">
                            <h5 class="mb-0"><?php echo htmlspecialchars($application['job_title']); ?></h5>
                            <span class="badge rounded-pill <?php 
                                echo $application['status'] == 'pending' ? 'badge-pending' : 
                                    ($application['status'] == 'accepted' ? 'badge-accepted' : 'badge-rejected'); 
                            ?>">
                                <?php echo ucfirst(htmlspecialchars($application['status'])); ?>
                            </span>
                        </div>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6">
                                <h6>Applicant Information</h6>
                                <p><strong>Name:</strong> <?php echo htmlspecialchars($application['full_name']); ?></p>
                                <p><strong>Course:</strong> <?php echo htmlspecialchars($application['course']); ?></p>
                                <p><strong>Graduation Year:</strong> <?php echo htmlspecialchars($application['graduation_year']); ?></p>
                                <p><strong>Skills:</strong> <?php echo htmlspecialchars($application['skills']); ?></p>
                            </div>
                            <div class="col-md-6">
                                <h6>Application Details</h6>
                                <p><strong>Applied On:</strong> <?php echo htmlspecialchars($application['apply_date']); ?></p>
                                <p><strong>Cover Letter:</strong></p>
                                <div class="border p-3 rounded">
                                    <?php echo nl2br(htmlspecialchars($application['cover_letter'])); ?>
                                </div>
                            </div>
                        </div>
                        
                        <?php if ($application['status'] == 'pending'): ?>
                            <div class="mt-3 d-flex gap-2">
                                <form action="update_application.php" method="POST" class="d-inline">
                                    <input type="hidden" name="application_id" value="<?php echo $application['id']; ?>">
                                    <input type="hidden" name="status" value="accepted">
                                    <button type="submit" class="btn btn-success btn-action">
                                        <i class="fas fa-check me-2"></i>Accept
                                    </button>
                                </form>
                                <form action="update_application.php" method="POST" class="d-inline">
                                    <input type="hidden" name="application_id" value="<?php echo $application['id']; ?>">
                                    <input type="hidden" name="status" value="rejected">
                                    <button type="submit" class="btn btn-danger btn-action">
                                        <i class="fas fa-times me-2"></i>Reject
                                    </button>
                                </form>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endwhile; ?>
        <?php else: ?>
            <div class="alert alert-info">
                No applications found for your posted jobs.
            </div>
        <?php endif; ?>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html> 