<?php
require_once 'auth.php';
require_once 'database_functions_enhanced.php';
requireLogin();

$user = getCurrentUser();

// Get department filter from GET parameter
$department_filter = (isset($_GET['department']) && $_GET['department'] !== '') ? intval($_GET['department']) : null;

// Check if user should see department filter
$show_department_filter = shouldShowDepartmentFilter($user);

$pending_evaluations = [];

if (canEvaluate()) {
    $pending_evaluations = getPendingEvaluationsForEvaluator($user['role_id'], $department_filter, $user);
}

// Get all departments for filter dropdown
$departments = getAllDepartments();
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pending Evaluations - Performance Evaluation System</title>
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
                    <h3><?php echo htmlspecialchars(getCurrentUser()["fullname"]); ?></h3>
                    <p><?php echo htmlspecialchars(getCurrentUser()["role_name"]); ?> - Evaluator</p>
                    <button onclick="location.href='dashboard.php'" class="btn btn-secondary">Back to Dashboard</button>
                    <button onclick="location.href='dashboard.php'" class="logout-btn">Logout</button>
                </div>
            </div>
        </div>

        <!-- Sequential Workflow Information -->
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">Sequential Evaluation Process</h3>
            </div>
            <div class="alert alert-info">
                <p><strong>Your Role:</strong> <?php echo htmlspecialchars($user['role_name']); ?></p>
                <p><strong>Evaluation Order:</strong></p>
                <div style="display: flex; align-items: center; gap: 15px; margin: 15px 0;">
                    <div style="background: <?php echo $user['role_id'] == 1 ? '#27ae60' : '#3498db'; ?>; color: white; padding: 8px 12px; border-radius: 5px; font-size: 12px;">
                        1. HR <?php echo $user['role_id'] == 1 ? '(You)' : ''; ?>
                    </div>
                    <span style="color: #7f8c8d;">→</span>
                    <div style="background: <?php echo $user['role_id'] == 4 ? '#27ae60' : '#9b59b6'; ?>; color: white; padding: 8px 12px; border-radius: 5px; font-size: 12px;">
                        2. Shift Leader <?php echo $user['role_id'] == 4 ? '(You)' : ''; ?>
                    </div>
                    <span style="color: #7f8c8d;">→</span>
                    <div style="background: <?php echo $user['role_id'] == 3 ? '#27ae60' : '#e67e22'; ?>; color: white; padding: 8px 12px; border-radius: 5px; font-size: 12px;">
                        3. Supervisor <?php echo $user['role_id'] == 3 ? '(You)' : ''; ?>
                    </div>
                    <span style="color: #7f8c8d;">→</span>
                    <div style="background: <?php echo $user['role_id'] == 2 ? '#27ae60' : '#34495e'; ?>; color: white; padding: 8px 12px; border-radius: 5px; font-size: 12px;">
                        4. Manager <?php echo $user['role_id'] == 2 ? '(You)' : ''; ?>
                    </div>
                </div>
                <p style="color: #4B3B2A; font-size: 14px;">
                    You can only evaluate employees when it's your turn in the sequence.
                    Once you complete an evaluation, the next evaluator will be automatically notified.
                </p>
            </div>
        </div>

        <div class="card">
            <div class="card-header">
                <h2 class="card-title">Evaluations Awaiting Your Review</h2>
                <div style="display: flex; gap: 10px; align-items: center; margin-top: 10px;">
                    <!-- Department Filter - Only show for HR role -->
                    <?php if ($show_department_filter): ?>
                        <form method="GET" style="display: flex; gap: 10px; align-items: center;">
                            <label for="department" style="font-weight: bold;">Filter by Department:</label>
                            <select name="department" id="department" class="form-control" style="width: auto;" onchange="this.form.submit()">
                                <option value="">All Departments</option>
                                <?php foreach ($departments as $dept): ?>
                                    <option value="<?php echo $dept['id']; ?>" <?php echo ($department_filter == $dept['id']) ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($dept['name']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </form>
                    <?php endif; ?>
                </div>
                <p style="color: #4B3B2A; margin-top: 10px;">
                    These evaluations are currently assigned to you in the sequential workflow.
                </p>
            </div>
            <?php if (!empty($pending_evaluations)): ?>
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
                                    <a href="evaluate.php?id=<?php echo $evaluation['id']; ?>" class="btn btn-primary">Start Evaluation</a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>

                <div class="alert alert-warning" style="margin-top: 20px;">
                    <p><strong>Important:</strong></p>
                    <ul>
                        <li>You must complete your evaluation before the next evaluator can access it</li>
                        <li>Once submitted, you cannot modify your evaluation</li>
                        <li>The next evaluator will be automatically notified when you complete your evaluation</li>
                    </ul>
                </div>
            <?php else: ?>
                <div style="text-align: center; padding: 40px; color: #7f8c8d;">
                    <h3>No Pending Evaluations</h3>
                    <p>You don't have any evaluations awaiting your review at this time.</p>
                    <p>When it's your turn in the evaluation sequence, evaluations will appear here.</p>
                </div>
            <?php endif; ?>
        </div>
    </div>
</body>

</html>