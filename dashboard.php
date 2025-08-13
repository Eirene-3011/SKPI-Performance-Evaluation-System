<?php
require_once 'auth.php';
require_once 'database_functions_enhanced.php';
require_once 'email_functions.php';

requireLogin();

// Check if user is admin and redirect to admin dashboard
if (isAdmin()) {
    header("Location: admin_dashboard.php");
    exit();
}

$user = getCurrentUser();
$user_evaluations = getEmployeeEvaluations($user['id']);
$pending_evaluations = [];

// Get pending evaluations if user can evaluate
if (canEvaluate()) {
    $pending_evaluations = getPendingEvaluationsForEvaluator($user["role_id"], null, $user);
}

// Handle logout
if (isset($_GET['logout'])) {
    logoutUser();
}

// Get statistics
$total_employees = count(getAllEmployees());
$total_evaluations = count($user_evaluations);
$completed_evaluations = count(array_filter($user_evaluations, function ($eval) {
    return $eval['status'] == 'completed';
}));
$pending_count = count($pending_evaluations);
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>Dashboard - Performance Evaluation System</title>
    <link rel="stylesheet" href="assets/style.css" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css" />

    <!-- Animation CSS -->
    <style>
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
    </style>
</head>

<body>
    <div class="container">
        <!-- Header -->
        <div class="header">
            <div class="header-content">
                <div class="logo-section">
                    <img src="assets/seiwa.logo.png" alt="Seiwa Kaiun Philippines Inc." class="logo" />
                    <div class="company-info">
                        <h1>Performance Evaluation System</h1>
                        <p>Seiwa Kaiun Philippines Inc.</p>
                    </div>
                </div>
                <div class="user-info">
                    <h3><?php echo htmlspecialchars($user['fullname']); ?></h3>
                    <p><?php echo htmlspecialchars($user['role_name']); ?> - <?php echo htmlspecialchars($user['department_name']); ?></p>
                    <p>Employee ID: <?php echo htmlspecialchars($user['card_no']); ?></p>
                    <button onclick="location.href='profile.php'" class="btn btn-secondary">My Profile</button>
                    <button onclick="location.href='?logout=1'" class="logout-btn">Logout</button>
                </div>
            </div>
        </div>

        <!-- Dashboard Statistics -->
        <div class="dashboard-grid">
            <div class="stat-card">
                <div class="stat-content">
                    <div class="stat-number"><?php echo $total_employees; ?></div>
                </div>
                <i class="fas fa-users"></i>
                <div class="stat-label">Total Employees</div>
            </div>

            <?php if ($user['role_id'] != 1): ?>
                <div class="stat-card">
                    <div class="stat-content">
                        <div class="stat-number"><?php echo $total_evaluations; ?></div>
                        <i class="fas fa-tasks"></i>
                    </div>
                    <div class="stat-label">My Evaluations</div>
                </div>
            <?php endif; ?>

            <?php if (canEvaluate()): ?>
                <div class="stat-card">
                    <div class="stat-content">
                        <div class="stat-number"><?php echo $pending_count; ?></div>
                        <i class="fas fa-hourglass-half"></i>
                    </div>
                    <div class="stat-label">Pending Reviews</div>
                </div>
            <?php endif; ?>
        </div>

        <!-- Quick Actions -->
        <div class="card">
            <div class="card-header">
                <h2 class="card-title">Quick Actions</h2>
            </div>
            <div>
                <?php if (hasRole(1)): // Admin/HR 
                ?>
                    <!-- Admin links commented out -->
                <?php endif; ?>

                <?php if (canEvaluate() && $pending_count > 0): ?>
                    <a href="pending_evaluations.php" class="btn btn-primary">Review Pending Evaluations (<?php echo $pending_count; ?>)</a>
                <?php endif; ?>

                <?php if ($user['role_id'] != 1): ?>
                    <a href="my_evaluations.php" class="btn btn-primary">View My Evaluations</a>
                <?php endif; ?>
                <?php if ($user['role_id'] != 5): ?>
                    <a href="evaluation_history.php" class="btn btn-primary">View Evaluation History</a>
                <?php endif; ?>
            </div>
        </div>

        <!-- Pending Evaluations for Evaluators -->
        <?php if (canEvaluate() && !empty($pending_evaluations)): ?>
            <div class="card">
                <div class="card-header">
                    <h2 class="card-title">Pending Evaluations Requiring Your Review</h2>
                </div>
                <table class="table">
                    <thead>
                        <tr>
                            <th>Employee</th>
                            <th>Employee ID</th>
                            <th>Department</th>
                            <th>Position</th>
                            <th>Evaluation Reason</th>
                            <th>Created Date</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($pending_evaluations as $evaluation): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($evaluation['fullname']); ?></td>
                                <td><?php echo htmlspecialchars($evaluation['card_no']); ?></td>
                                <td><?php echo htmlspecialchars($evaluation['department_name']); ?></td>
                                <td><?php echo htmlspecialchars($evaluation['position_name']); ?></td>
                                <td><?php echo htmlspecialchars($evaluation['evaluation_reason']); ?></td>
                                <td><?php echo date('M d, Y', strtotime($evaluation['created_date'])); ?></td>
                                <td>
                                    <a href="evaluate.php?id=<?php echo $evaluation['id']; ?>" class="btn btn-primary">Evaluate</a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>

        <!-- Recent Evaluations -->
        <?php if (!empty($user_evaluations)): ?>
            <div class="card">
                <div class="card-header">
                    <h2 class="card-title">My Recent Evaluations</h2>
                </div>
                <table class="table">
                    <thead>
                        <tr>
                            <th>Evaluation Reason</th>
                            <th>Period Covered</th>
                            <th>Status</th>
                            <th>Created Date</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach (array_slice($user_evaluations, 0, 5) as $evaluation): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($evaluation['evaluation_reason']); ?></td>
                                <td>
                                    <?php echo date('M d, Y', strtotime($evaluation['period_covered_from'])); ?> -
                                    <?php echo date('M d, Y', strtotime($evaluation['period_covered_to'])); ?>
                                </td>
                                <td>
                                    <span class="badge badge-<?php echo $evaluation['status']; ?>">
                                        <?php echo ucfirst($evaluation['status']); ?>
                                    </span>
                                </td>
                                <td><?php echo date('M d, Y', strtotime($evaluation['created_date'])); ?></td>
                                <td>
                                    <a href="view_evaluation.php?id=<?php echo $evaluation['id']; ?>" class="btn btn-secondary" style="margin-bottom: 10px;">View</a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                <?php if (count($user_evaluations) > 5): ?>
                    <div style="text-align: center; margin-top: 15px;">
                        <a href="my_evaluations.php" class="btn btn-primary">View All Evaluations</a>
                    </div>
                <?php endif; ?>
            </div>
        <?php else: ?>
            <div class="card">
                <div class="card-header">
                    <h2 class="card-title">My Evaluations</h2>
                </div>
                <div style="text-align: center; padding: 40px; color: #7f8c8d;">
                    <p>No evaluations found. Your evaluations will appear here once they are created by HR.</p>
                </div>
            </div>
        <?php endif; ?>
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
        </script>