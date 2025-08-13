<?php
require_once 'auth.php';
require_once 'database_functions_enhanced.php';
requireLogin();

$user = getCurrentUser();
$user_evaluations = getEmployeeEvaluations($user['id']);
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Evaluations - Performance Evaluation System</title>
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
                    <p><?php echo htmlspecialchars(getCurrentUser()["role_name"]); ?></p>
                    <button onclick="location.href='dashboard.php'" class="btn btn-secondary">Back to Dashboard</button>
                    <button onclick="location.href='dashboard.php'" class="logout-btn">Logout</button>
                </div>
            </div>
        </div>

        <div class="card">
            <div class="card-header">
                <h2 class="card-title">My Evaluations</h2>
                <p style="color: #7f8c8d; margin-top: 10px;">View the status of your performance evaluations and track their progress through the evaluation workflow.</p>
            </div>
            <?php if (!empty($user_evaluations)): ?>
                <table class="table">
                    <thead>
                        <tr>
                            <th>Evaluation Reason</th>
                            <th>Period Covered</th>
                            <th>Status</th>
                            <th>Current Evaluator</th>
                            <th>Created Date</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($user_evaluations as $evaluation): ?>
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
                                <td>
                                    <?php if ($evaluation['status'] == 'pending'): ?>
                                        <span style="color: #e67e22; font-weight: bold;">
                                            <?php echo htmlspecialchars($evaluation['current_evaluator_role_name']); ?>
                                        </span>
                                    <?php else: ?>
                                        <span style="color: #27ae60;">
                                            ✓ Completed
                                        </span>
                                    <?php endif; ?>
                                </td>
                                <td><?php echo date('M d, Y', strtotime($evaluation['created_date'])); ?></td>
                                <td>
                                    <a href="view_evaluation.php?id=<?php echo $evaluation['id']; ?>" class="btn btn-secondary" style="margin-bottom: 10px;">View Details</a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>

                <!-- Evaluation Process Information -->
                <div class="card" style="margin-top: 20px;">
                    <div class="card-header">
                        <h3 class="card-title">Evaluation Process</h3>
                    </div>
                    <div style="padding: 20px;">
                        <p><strong>Sequential Evaluation Flow:</strong></p>
                        <div style="display: flex; align-items: center; gap: 15px; margin: 15px 0;">
                            <div style="background: #3498db; color: white; padding: 8px 12px; border-radius: 5px; font-size: 12px;">1. HR</div>
                            <span style="color: #7f8c8d;">→</span>
                            <div style="background: #9b59b6; color: white; padding: 8px 12px; border-radius: 5px; font-size: 12px;">2. Shift Leader</div>
                            <span style="color: #7f8c8d;">→</span>
                            <div style="background: #e67e22; color: white; padding: 8px 12px; border-radius: 5px; font-size: 12px;">3. Supervisor</div>
                            <span style="color: #7f8c8d;">→</span>
                            <div style="background: #27ae60; color: white; padding: 8px 12px; border-radius: 5px; font-size: 12px;">4. Manager</div>
                        </div>
                        <p style="color: #7f8c8d; font-size: 14px;">
                            Each evaluator must complete their assessment before the next evaluator can access the evaluation.
                            You will be notified when your evaluation is completed by all evaluators.
                        </p>
                    </div>
                </div>
            <?php else: ?>
                <div style="text-align: center; padding: 40px; color: #7f8c8d;">
                    <h3>No Evaluations Found</h3>
                    <p>You don't have any performance evaluations yet.</p>
                    <p>When evaluations are created for you, they will appear here and you can track their progress.</p>
                </div>
            <?php endif; ?>
        </div>
    </div>
</body>

</html>