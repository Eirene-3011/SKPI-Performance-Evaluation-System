<?php
require_once 'auth.php';
require_once 'database_functions_enhanced.php';
require_once 'admin_functions.php';

// Check if user is logged in and is an admin
requireAdmin();

$user = getCurrentUser();

// Get all evaluations
$evaluations = getAllEvaluations();

// Handle logout
if (isset($_GET['logout'])) {
    logoutUser();
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>Evaluation Workflow Status - Admin - Performance Evaluation System</title>
    <link rel="stylesheet" href="assets/style.css" />
    <style>
        /* (Keep your existing CSS here - omitted for brevity) */
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
                    <p><strong>ADMIN</strong> - <?php echo htmlspecialchars($user['department_name']); ?></p>
                    <p>Employee ID: <?php echo htmlspecialchars($user['card_no']); ?></p>
                    <button onclick="location.href='admin_dashboard.php'" class="btn btn-secondary">Back to Dashboard</button>
                    <button onclick="location.href='?logout=1'" class="logout-btn">Logout</button>
                </div>
            </div>
        </div>

        <!-- Page Content -->
        <div class="card">
            <div class="card-header">
                <h2 class="card-title">Evaluation Workflow Status</h2>
            </div>

            <!-- Print/Export Buttons -->
            <div class="print-buttons">
                <button onclick="window.print()" class="btn btn-primary">Print Report</button>
                <button onclick="exportToPDF()" class="btn btn-secondary">Export to PDF</button>
            </div>

            <!-- Workflow Status Table -->
            <?php if (empty($evaluations)): ?>
                <div style="text-align: center; padding: 30px;">
                    <p>No evaluations found.</p>
                </div>
            <?php else: ?>
                <table class="workflow-table" id="workflowTable">
                    <thead>
                        <tr>
                            <th>Employee</th>
                            <th>Evaluation Reason</th>
                            <th>Created Date</th>
                            <th>Current Step</th>
                            <th>Workflow Progress</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($evaluations as $evaluation):
                            $workflow_status = getEvaluationWorkflowStatus($evaluation_id);

                            $unique_workflow_status = [];
                            $seen_roles = [];
                            foreach ($workflow_status as $step) {
                                if (!in_array($step['role_name'], $seen_roles)) {
                                    $unique_workflow_status[] = $step;
                                    $seen_roles[] = $step['role_name'];
                                }
                            }
                            $workflow_status = $unique_workflow_status;

                            $current_step = getCurrentEvaluationStep($evaluation['id']);

                            // Calculate progress percentage
                            $completed_steps = count(array_filter($workflow_status, function ($step) {
                                return $step['status'] === 'completed';
                            }));
                            $total_steps = count($workflow_status);
                            $progress_percentage = $total_steps > 0 ? ($completed_steps / $total_steps) * 100 : 0;
                        ?>
                            <tr>
                                <td><?php echo htmlspecialchars($evaluation['fullname']); ?></td>
                                <td><?php echo htmlspecialchars($evaluation['evaluation_reason']); ?></td>
                                <td><?php echo date('M d, Y', strtotime($evaluation['created_date'])); ?></td>
                                <td><?php echo htmlspecialchars($current_step); ?></td>
                                <td>
                                    <div class="workflow-steps">
                                        <?php foreach ($workflow_status as $index => $step):
                                            $step_class = '';
                                            if ($step['status'] === 'completed') {
                                                $step_class = 'completed';
                                            } elseif ($index === $completed_steps) {
                                                $step_class = 'active';
                                            }
                                        ?>
                                            <div class="workflow-step">
                                                <div class="step-circle <?php echo $step_class; ?>">
                                                    <?php echo $index + 1; ?>
                                                </div>
                                                <div class="step-label"><?php echo htmlspecialchars($step['role_name']); ?></div>
                                            </div>
                                        <?php endforeach; ?>
                                    </div>
                                    <div style="height: 5px; background-color: #f1f1f1; border-radius: 5px; overflow: hidden;">
                                        <div style="height: 100%; width: <?php echo $progress_percentage; ?>%; background-color: #3498db;"></div>
                                    </div>
                                    <div style="text-align: right; font-size: 12px; color: #7f8c8d;">
                                        <?php echo $completed_steps; ?> of <?php echo $total_steps; ?> steps completed
                                    </div>
                                </td>
                                <td>
                                    <span class="status-badge status-<?php echo htmlspecialchars($evaluation['status']); ?>">
                                        <?php echo ucfirst(htmlspecialchars($evaluation['status'])); ?>
                                    </span>
                                </td>
                                <td>
                                    <a href="admin_view_evaluation.php?id=<?php echo $evaluation['id']; ?>" class="btn btn-small btn-primary">View</a>
                                    <a href="admin_detailed_report.php?id=<?php echo $evaluation['id']; ?>" class="btn btn-small btn-secondary">Report</a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>

            <!-- Summary Statistics -->
            <?php if (!empty($evaluations)): ?>
                <div style="margin-top: 30px; padding: 15px; background-color: #f8f9fa; border-radius: 5px;">
                    <h3>Workflow Summary</h3>
                    <div style="display: flex; flex-wrap: wrap; gap: 20px; margin-top: 15px;">
                        <?php
                        // Count evaluations by status
                        $completed_count = count(array_filter($evaluations, function ($eval) {
                            return $eval['status'] === 'completed';
                        }));
                        $pending_count = count($evaluations) - $completed_count;

                        // Count evaluations by current step
                        $step_counts = [];
                        foreach ($evaluations as $evaluation) {
                            if ($evaluation['status'] === 'pending') {
                                $current_step = getCurrentEvaluationStep($evaluation['id']);
                                if (!isset($step_counts[$current_step])) {
                                    $step_counts[$current_step] = 0;
                                }
                                $step_counts[$current_step]++;
                            }
                        }
                        ?>
                        <div style="flex: 1; min-width: 200px;">
                            <p><strong>Total Evaluations:</strong> <?php echo count($evaluations); ?></p>
                            <p><strong>Completed Evaluations:</strong> <?php echo $completed_count; ?></p>
                            <p><strong>Pending Evaluations:</strong> <?php echo $pending_count; ?></p>
                        </div>

                        <div style="flex: 1; min-width: 200px;">
                            <p><strong>Current Step Breakdown:</strong></p>
                            <?php foreach ($step_counts as $step => $count): ?>
                                <p><?php echo htmlspecialchars($step); ?>: <?php echo $count; ?></p>
                            <?php endforeach; ?>
                            <?php if (empty($step_counts)): ?>
                                <p>No pending evaluations</p>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            <?php endif; ?>

            <!-- Back Button -->
            <div style="text-align: center; margin-top: 20px;">
                <a href="admin_dashboard.php" class="btn btn-primary">Back to Dashboard</a>
            </div>
        </div>
    </div>

    <script>
        // Export to PDF function
        function exportToPDF() {
            // In a real implementation, you would use a library like jsPDF
            // For this example, we'll just trigger the print dialog which can save as PDF
            window.print();
        }
    </script>
</body>

</html>