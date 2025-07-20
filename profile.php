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
$company = null;

try {
    // Get company profile and user data
    $user_id = $_SESSION['user_id'];
    $query = "SELECT c.*, u.email, u.user_type 
              FROM users u 
              LEFT JOIN company_profiles c ON c.user_id = u.id 
              WHERE u.id = ?";
    
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

    // If company profile doesn't exist, create one
    if ($company && !isset($company['name'])) {
        $create_query = "INSERT INTO company_profiles (user_id, name, industry, location, website, description) 
                        VALUES (?, '', '', '', '', '')";
        
        $create_stmt = mysqli_prepare($conn, $create_query);
        if (!$create_stmt) {
            throw new Exception("Error preparing statement: " . mysqli_error($conn));
        }

        mysqli_stmt_bind_param($create_stmt, "i", $user_id);
        if (!mysqli_stmt_execute($create_stmt)) {
            throw new Exception("Error creating profile: " . mysqli_stmt_error($create_stmt));
        }

        // Set default values for the company profile
        $company['name'] = '';
        $company['industry'] = '';
        $company['location'] = '';
        $company['website'] = '';
        $company['description'] = '';
    }

    if (!$company) {
        throw new Exception("Error retrieving user data.");
    }

    // Handle form submission
    if ($_SERVER['REQUEST_METHOD'] == 'POST') {
        $name = trim($_POST['name']);
        $industry = trim($_POST['industry']);
        $location = trim($_POST['location']);
        $website = trim($_POST['website']);
        $description = trim($_POST['description']);

        // Validate input
        if (empty($name) || empty($industry) || empty($location)) {
            throw new Exception("Please fill in all required fields.");
        }

        // Update company profile
        $query = "INSERT INTO company_profiles (user_id, name, industry, location, website, description) 
                 VALUES (?, ?, ?, ?, ?, ?) 
                 ON DUPLICATE KEY UPDATE 
                 name = VALUES(name), 
                 industry = VALUES(industry), 
                 location = VALUES(location), 
                 website = VALUES(website), 
                 description = VALUES(description)";
        
        $stmt = mysqli_prepare($conn, $query);
        if (!$stmt) {
            throw new Exception("Error preparing statement: " . mysqli_error($conn));
        }

        mysqli_stmt_bind_param($stmt, "isssss", 
            $user_id,
            $name,
            $industry,
            $location,
            $website,
            $description
        );

        if (mysqli_stmt_execute($stmt)) {
            $success = "Profile updated successfully!";
            // Refresh company data
            $company['name'] = $name;
            $company['industry'] = $industry;
            $company['location'] = $location;
            $company['website'] = $website;
            $company['description'] = $description;
        } else {
            throw new Exception("Error updating profile: " . mysqli_stmt_error($stmt));
        }
    }
} catch (Exception $e) {
    $error = $e->getMessage();
}

// Ensure company array has all required keys to prevent warnings
$company = array_merge([
    'email' => '',
    'user_type' => 'company',
    'name' => '',
    'industry' => '',
    'location' => '',
    'website' => '',
    'description' => ''
], $company ?? []);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Company Profile - Job Portal</title>
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
        .profile-card {
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
        .status-badge {
            font-size: 0.9rem;
            padding: 0.5rem 1rem;
            border-radius: 10px;
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
                        <a class="nav-link" href="post_job.php">Post New Job</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="view_applications.php">View Applications</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link active" href="profile.php">Company Profile</a>
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
                <div class="profile-card p-4">
                    <div class="d-flex justify-content-between align-items-center mb-4">
                        <h3>Company Profile</h3>
                        <span class="status-badge <?php echo $company['user_type'] == 'company' ? 'bg-success' : 'bg-danger'; ?>">
                            <?php echo $company['user_type'] == 'company' ? 'Active' : 'Inactive'; ?>
                        </span>
                    </div>

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
                            <label for="email" class="form-label">Email</label>
                            <input type="email" class="form-control" id="email" value="<?php echo htmlspecialchars($company['email']); ?>" disabled>
                        </div>

                        <div class="mb-3">
                            <label for="name" class="form-label">Company Name <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="name" name="name" value="<?php echo htmlspecialchars($company['name']); ?>" required>
                        </div>

                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="industry" class="form-label">Industry <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" id="industry" name="industry" value="<?php echo htmlspecialchars($company['industry']); ?>" required>
                            </div>

                            <div class="col-md-6 mb-3">
                                <label for="location" class="form-label">Location <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" id="location" name="location" value="<?php echo htmlspecialchars($company['location']); ?>" required>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label for="website" class="form-label">Website</label>
                            <input type="url" class="form-control" id="website" name="website" value="<?php echo htmlspecialchars($company['website']); ?>">
                        </div>

                        <div class="mb-3">
                            <label for="description" class="form-label">Company Description</label>
                            <textarea class="form-control" id="description" name="description" rows="5"><?php echo htmlspecialchars($company['description']); ?></textarea>
                        </div>

                        <div class="d-grid">
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