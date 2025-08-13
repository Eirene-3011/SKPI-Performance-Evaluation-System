<?php
require_once 'auth.php';
require_once 'database_functions_enhanced.php';
require_once 'email_functions.php';


// Define Admin role ID
define('ADMIN_ROLE_ID', 0); // Admin role ID is 0

// Check if user is logged in and is an admin
requireLogin();
if (!hasRole(ADMIN_ROLE_ID)) {
    header("Location: dashboard.php?error=access_denied");
    exit();
}

$user = getCurrentUser();

// Get statistics
$total_employees = count(getAllEmployees());
$all_evaluations = getAllEvaluations(); // New function to get all evaluations
$total_evaluations = count($all_evaluations);
$completed_evaluations = count(array_filter($all_evaluations, function ($eval) {
    return $eval['status'] == 'completed';
}));
$pending_evaluations = count(array_filter($all_evaluations, function ($eval) {
    return $eval['status'] == 'pending';
}));

// Handle logout
if (isset($_GET['logout'])) {
    logoutUser();
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - Performance Evaluation System</title>
    <link rel="stylesheet" href="assets/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">

    <style>
        .admin-header {
            background-color: #C83F12;
            color: white;
            padding: 10px;
            margin-top: 0px;
            margin-bottom: 5px;
            border-radius: 5px;
            text-align: center;
        }

        .admin-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }

        .admin-card {
            background-color: #FEF9E1;
            border-radius: 8px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            padding: 20px;
            transition: transform 0.3s ease;
            margin-top: 10px;
        }

        .admin-card:hover {
            transform: translateY(-5px);
        }

        .admin-card h3 {
            color: #A31D1D;
            margin-top: 0;
            border-bottom: 1px solid #e0e0e0;
            padding-bottom: 10px;
        }

        .admin-card p {
            color: #4B3B2A;
            margin-bottom: 15px;
        }

        .admin-card .btn-group {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
        }

        @keyframes fadeInUp {
            0% {
                opacity: 0;
                transform: translateY(20px);
            }

            100% {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .stat-card {
            opacity: 0;
            animation: fadeInUp 0.6s ease forwards;
        }

        /* Add slight delay between cards */
        .stat-card:nth-child(1) {
            animation-delay: 0.1s;
        }

        .stat-card:nth-child(2) {
            animation-delay: 0.3s;
        }

        .stat-card:nth-child(3) {
            animation-delay: 0.5s;
        }

        .stat-card:nth-child(4) {
            animation-delay: 0.7s;
        }

        /* Scroll-to-top button bounce on hover */
        #scrollTopBtn:hover {
            animation: bounce 0.6s;
        }

        @keyframes bounce {

            0%,
            20%,
            50%,
            80%,
            100% {
                transform: translateY(0);
            }

            40% {
                transform: translateY(-10px);
            }

            60% {
                transform: translateY(-5px);
            }
        }

        .dashboard-grid-admin {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 20px;
            padding: 20px;
        }

        .stat-card {
            background: linear-gradient(to right, #8A0000, #ff5e57);
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            text-align: center;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            height: 180px;
            /* Adjusted height for a more consistent look */
            transition: transform 0.4s ease, opacity 0.4s;
        }

        .stat-number {
            font-size: 36px;
            /* Larger number for better visibility */
            font-weight: bold;
            color: #fff;
            /* White text for high contrast */
        }

        .stat-label {
            font-size: 16px;
            color: #fff;
            /* White text */
            margin-top: 10px;
        }

        .stat-card i {
            font-size: 40px;
            /* Adjusted icon size to match image */
            color: #fff;
            /* White icon color */
            margin-top: 10px;
            /* Space between number and icon */
        }

        .stat-card:hover {
            transform: scale(1.1);
            opacity: 0.8;
        }
    </style>
</head>

<body>
    <div class="container">
        <!-- Header -->
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
                    <div class="btn-group-profile">
                        <button onclick="location.href='profile.php'" class="btn btn-secondary">My Profile</button>
                        <button onclick="location.href='?logout=1'" class="logout-btn">Logout</button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Admin Header -->
        <div class="admin-header">
            <h2>ADMIN CONTROL PANEL</h2>
            <p>Welcome to the administrator dashboard. You have full access to all system features.</p>
        </div>

        <!-- Dashboard Statistics -->
        <div class="dashboard-grid-admin">
            <div class="stat-card">
                <div class="stat-number"><?php echo $total_employees; ?></div>
                <i class="fas fa-users"></i> <!-- Icon for Total Employees -->
                <div class="stat-label">Total Employees</div>
            </div>

            <div class="stat-card">
                <div class="stat-number"><?php echo $total_evaluations; ?></div>
                <i class="fas fa-tasks"></i> <!-- Icon for Total Evaluations -->
                <div class="stat-label">Total Evaluations</div>
            </div>

            <div class="stat-card">
                <div class="stat-number"><?php echo $completed_evaluations; ?></div>
                <i class="fas fa-check-circle"></i> <!-- Icon for Completed Evaluations -->
                <div class="stat-label">Completed Evaluations</div>
            </div>

            <div class="stat-card">
                <div class="stat-number"><?php echo $pending_evaluations; ?></div>
                <i class="fas fa-hourglass-half"></i> <!-- Icon for Pending Evaluations -->
                <div class="stat-label">Pending Evaluations</div>
            </div>
        </div>


        <!-- Admin Functions Grid -->
        <div class="admin-grid">
            <!-- Evaluation Management -->
            <div class="admin-card">
                <h3>Evaluation Management</h3>
                <p>Create and manage employee evaluations.</p>
                <div class="btn-group">
                    <a href="admin_create_evaluation.php" class="btn btn-primary">Create New Evaluation</a>
                    <a href="admin_view_evaluations.php" class="btn btn-secondary-admin">View All Evaluations</a>
                    <a href="admin_bulk_delete_evaluations.php" class="btn btn-danger">Delete Evaluations</a>
                </div>
            </div>
        </div>

        <!-- Recent Activity -->
        <div class="card">
            <div class="card-header">
                <h2 class="card-title">Recent Evaluation Activity</h2>
            </div>
            <table class="table">
                <thead>
                    <tr>
                        <th>Employee</th>
                        <th>Evaluation Reason</th>
                        <th>Status</th>
                        <th>Last Updated</th>
                        <th>Current Step</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    // Get recent evaluations with workflow status
                    $recent_evaluations = array_slice($all_evaluations, 0, 10);
                    foreach ($recent_evaluations as $evaluation):
                        $workflow_status = getEvaluationWorkflowStatus($evaluation['id']); // New function to get workflow status
                        $current_step = getCurrentEvaluationStep($evaluation['id']); // New function to get current step
                    ?>
                        <tr>
                            <td><?php echo htmlspecialchars($evaluation['fullname']); ?></td>
                            <td><?php echo htmlspecialchars($evaluation['evaluation_reason']); ?></td>
                            <td>
                                <span class="badge badge-<?php echo $evaluation['status']; ?>">
                                    <?php echo ucfirst($evaluation['status']); ?>
                                </span>
                            </td>
                            <td><?php echo date('M d, Y', strtotime($evaluation['updated_date'])); ?></td>
                            <td><?php echo htmlspecialchars($current_step); ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            <div class="back-to-dashboard-container">
                <a href="admin_view_evaluations.php" class="btn btn-primary">View All Evaluations</a>
            </div>
        </div>
    </div>


    <script>
        document.addEventListener('DOMContentLoaded', function() {
            console.log('Admin Dashboard loaded');

            // Animate count-up numbers for stats
            function animateCountUp(element, endValue, duration = 1500) {
                let startValue = 0;
                let startTime = null;

                function step(currentTime) {
                    if (!startTime) startTime = currentTime;
                    const progress = currentTime - startTime;
                    const progressRatio = Math.min(progress / duration, 1);
                    const currentValue = Math.floor(progressRatio * endValue);
                    element.textContent = currentValue;
                    if (progress < duration) {
                        requestAnimationFrame(step);
                    } else {
                        element.textContent = endValue; // Ensure final value
                    }
                }
                requestAnimationFrame(step);
            }

            const statNumbers = document.querySelectorAll('.stat-number');
            statNumbers.forEach((el) => {
                const endVal = parseInt(el.textContent, 10);
                animateCountUp(el, endVal);
            });
        });

        // Scroll-to-top button display logic (keep as is)
        window.addEventListener('scroll', function() {
            const btn = document.getElementById('scrollTopBtn');
            if (window.scrollY > 300) {
                btn.style.display = 'block';
            } else {
                btn.style.display = 'none';
            }
        });

        function scrollToTop() {
            window.scrollTo({
                top: 0,
                behavior: 'smooth'
            });
        }

        // Add any JavaScript functionality here
        document.addEventListener('DOMContentLoaded', function() {
            console.log('Admin Dashboard loaded');
        });
    </script>

    <button onclick="scrollToTop()" id="scrollTopBtn" title="Go to top">↑</button>
    <script>
        window.addEventListener('scroll', function() {
            const btn = document.getElementById('scrollTopBtn');
            if (window.scrollY > 300) {
                btn.style.display = 'block';
            } else {
                btn.style.display = 'none';
            }
        });

        function scrollToTop() {
            window.scrollTo({
                top: 0,
                behavior: 'smooth'
            });
        }
    </script>
</body>

</html>