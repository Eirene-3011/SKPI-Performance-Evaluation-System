<?php
require_once 'auth.php';
require_once 'database_functions_enhanced.php';

requireLogin();

$user = getCurrentUser();

// Get department filter from GET parameter
$department_filter = isset($_GET['department']) && $_GET['department'] !== '' ? intval($_GET['department']) : null;

// Get completed evaluations with optional department filter
$completed_evaluations = getCompletedEvaluationsByUser($user['id'], $department_filter);

// Get all departments for filter dropdown
$departments = getAllDepartments();

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
    <title>Evaluation History - Performance Evaluation System</title>
    <link rel="stylesheet" href="assets/style.css">
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
                    <p><?php echo htmlspecialchars($user['role_name']); ?> - <?php echo htmlspecialchars($user['department_name']); ?></p>
                    <p>Employee ID: <?php echo htmlspecialchars($user['card_no']); ?></p>
                    <button onclick="location.href='profile.php'" class="btn btn-secondary">My Profile</button>
                    <button onclick="location.href='?logout=1'" class="logout-btn">Logout</button>
                </div>
            </div>
        </div>

        <div class="card">
            <div class="card-header">
                <h2 class="card-title">Evaluation History</h2>
                <div style="display: flex; gap: 10px; align-items: center;">
                    <!-- Department Filter -->
                    <form method="GET" style="display: flex; gap: 10px; align-items: center;">
                        <label for="department" style="font-weight: bold;">Filter by Department:</label>
                        <select name="department" id="department" class="form-select" style="width: auto;" onchange="this.form.submit()">
                            <option value=""> -- All Departments -- </option>
                            <option value="1" <?php echo ($department_filter == 1) ? 'selected' : ''; ?>>OOS Department</option>
                            <option value="2" <?php echo ($department_filter == 2) ? 'selected' : ''; ?>>OJS Department</option>
                            <option value="3" <?php echo ($department_filter == 3) ? 'selected' : ''; ?>>HRGA Department</option>
                            <option value="4" <?php echo ($department_filter == 4) ? 'selected' : ''; ?>>MIS Department</option>
                            <option value="5" <?php echo ($department_filter == 5) ? 'selected' : ''; ?>>EXEC Department</option>
                            <option value="6" <?php echo ($department_filter == 6) ? 'selected' : ''; ?>>FINANCE Department</option>
                        </select>
                    </form>
                    <a href="dashboard.php" class="btn btn-primary" style="margin-top: 23px;">Back to Dashboard</a>
                </div>
            </div>
            <table class="table">
                <thead>
                    <tr>
                        <th>Employee</th>
                        <th>Department</th>
                        <th>Evaluation Reason</th>
                        <th>Status</th>
                        <th>Evaluation Date</th>
                        <th>Period Covered</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!empty($completed_evaluations)): ?>
                        <?php foreach ($completed_evaluations as $evaluation): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($evaluation['fullname']); ?></td>
                                <td><?php echo htmlspecialchars($evaluation['department_name']); ?></td>
                                <td><?php echo htmlspecialchars($evaluation['evaluation_reason']); ?></td>
                                <td>
                                    <span class="badge badge-<?php echo $evaluation['status']; ?>">
                                        <?php echo ucfirst($evaluation['status']); ?>
                                    </span>
                                </td>
                                <td><?php echo date('M d, Y', strtotime($evaluation['evaluation_date'])); ?></td>
                                <td><?php echo date('M d, Y', strtotime($evaluation['period_covered_from'])) . ' - ' . date('M d, Y', strtotime($evaluation['period_covered_to'])); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="7">No completed evaluations found.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
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