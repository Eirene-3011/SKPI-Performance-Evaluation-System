<?php
require_once 'auth.php';
require_once 'database_functions_enhanced.php';

requireLogin();
if (!hasRole(0)) { // Admin role ID is 0
    header("Location: dashboard.php?error=access_denied");
    exit();
}

$user = getCurrentUser();

?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add New Employee - Admin</title>
    <link rel="stylesheet" href="assets/style.css">
</head>

<body>
    <div class="container">
        <div class="header">
            <div class="header-content">
                <div class="logo-section">
                    <img src="assets/seiwa.logo.png" alt="Seiwa Kaiun Philippines Inc." class="logo">
                    <div class="company-info">
                        <h1>Performance Evaluation System</h1>
                        <p>Seiwa Kaiun Philippines Inc.</p>
                    </div>
                </div>
                <div class="user-info">
                    <h3><?php echo htmlspecialchars($user['fullname']); ?></h3>
                    <p><strong>ADMIN</strong> - <?php echo htmlspecialchars($user['department_name']); ?></p>
                    <p>Employee ID: <?php echo htmlspecialchars($user['card_no']); ?></p>
                    <button onclick="location.href='profile.php'" class="btn btn-secondary">My Profile</button>
                    <button onclick="location.href='?logout=1'" class="logout-btn">Logout</button>
                </div>
            </div>
        </div>

        <div class="card">
            <div class="card-header">
                <h2 class="card-title">Add New Employee</h2>
                <a href="admin_dashboard.php" class="btn btn-primary">Back to Dashboard</a>
            </div>
            <p>This page is under construction. Functionality to add a new employee will be implemented here.</p>
        </div>
    </div>
</body>

</html>